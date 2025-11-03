<?php

namespace App\Http\Controllers;

use App\Models\LiveMonitoringSession;
use App\Services\LiveMonitoringService;
use App\Jobs\LiveNetworkCapture;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Redis;
use Inertia\Inertia;

class LiveMonitoringController extends Controller
{
    protected $service;

    public function __construct(LiveMonitoringService $service)
    {
        $this->service = $service;
    }

    /**
     * Display live monitoring page
     */
    public function index()
    {
        $activeSessions = LiveMonitoringSession::where('user_id', Auth::id())
            ->where('status', 'active')
            ->with('packets')
            ->latest()
            ->get();

        $interfaces = $this->service->getAvailableInterfaces();

        return Inertia::render('NetworkAnalysis/LiveMonitoring', [
            'activeSessions' => $activeSessions,
            'interfaces' => $interfaces,
        ]);
    }

    /**
     * Get available network interfaces
     */
    public function getInterfaces()
    {
        $interfaces = $this->service->getAvailableInterfaces();

        return response()->json([
            'success' => true,
            'interfaces' => $interfaces,
        ]);
    }

    /**
     * Start live monitoring session
     */
    public function start(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'interface' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid interface provided',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = Auth::user();
            $interface = $request->input('interface');

            // Start new session
            $session = $this->service->startSession($user, $interface);

            // Dispatch job to start capturing packets
            // Note: This should be run in background
            dispatch(new LiveNetworkCapture($session));

            Log::info('Live monitoring start requested', [
                'user_id' => $user->id,
                'session_id' => $session->session_id,
                'interface' => $interface,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Live monitoring started successfully',
                'session' => [
                    'id' => $session->id,
                    'session_id' => $session->session_id,
                    'interface' => $session->interface,
                    'status' => $session->status,
                    'started_at' => $session->started_at->toIso8601String(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to start live monitoring', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to start live monitoring: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Stop live monitoring session
     */
    public function stop(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid session ID',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $session = LiveMonitoringSession::where('session_id', $request->session_id)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            $this->service->stopSession($session);

            return response()->json([
                'success' => true,
                'message' => 'Live monitoring stopped successfully',
                'statistics' => $this->service->getSessionStatistics($session),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to stop live monitoring', [
                'error' => $e->getMessage(),
                'session_id' => $request->session_id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to stop live monitoring: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get session status and statistics
     */
    public function status(Request $request, string $sessionId)
    {
        try {
            $session = LiveMonitoringSession::where('session_id', $sessionId)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            $statistics = $this->service->getSessionStatistics($session);
            $recentPackets = $this->service->getRecentPackets($session, 50);

            return response()->json([
                'success' => true,
                'statistics' => $statistics,
                'recent_packets' => $recentPackets,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found',
            ], 404);
        }
    }

    /**
     * Receive packet data from Python script (webhook endpoint)
     */
    public function receivePacket(Request $request)
    {
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'session_id' => 'required|string',
                'packet' => 'required|array',
                'api_token' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid packet data',
                ], 422);
            }

            // Verify API token
            $expectedToken = hash_hmac(
                'sha256',
                $request->session_id,
                config('app.key')
            );

            if (!hash_equals($expectedToken, $request->api_token)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid API token',
                ], 403);
            }

            // Find session
            $session = LiveMonitoringSession::where('session_id', $request->session_id)
                ->where('status', 'active')
                ->firstOrFail();

            // Process packet
            $this->service->processPacket($session, $request->packet);

            return response()->json([
                'success' => true,
                'message' => 'Packet received',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to receive packet', [
                'error' => $e->getMessage(),
                'session_id' => $request->session_id ?? 'unknown',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process packet',
            ], 500);
        }
    }

    /**
     * Get session history
     */
    public function history()
    {
        $sessions = LiveMonitoringSession::where('user_id', Auth::id())
            ->with('packets')
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'sessions' => $sessions,
        ]);
    }

    /**
     * Poll packets from Redis queue (Long Polling endpoint)
     * This replaces WebSocket for real-time packet streaming
     */
    public function pollPackets(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string',
            'batch_size' => 'integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request parameters',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $sessionId = $request->input('session_id');
            $batchSize = $request->input('batch_size', 10);
            
            // Verify session exists and belongs to user
            $session = LiveMonitoringSession::where('session_id', $sessionId)
                ->where('user_id', Auth::id())
                ->where('status', 'active')
                ->firstOrFail();

            // Get packets from Redis queue
            $packets = [];
            $queueKey = "live_packets:{$sessionId}";
            
            // If Redis PHP extension is missing while client is configured as phpredis, avoid errors
            if (config('database.redis.client') === 'phpredis' && !class_exists('\\Redis')) {
                Log::warning('PhpRedis configured but PHP Redis extension not loaded; serving empty packet batch', [
                    'session_id' => $sessionId,
                ]);
                return response()->json([
                    'success' => true,
                    'packets' => [],
                    'count' => 0,
                    'session_id' => $sessionId,
                    'fallback' => 'redis_extension_missing',
                ]);
            }
            
            try {
                for ($i = 0; $i < $batchSize; $i++) {
                    $packetData = Redis::lpop($queueKey);
                    
                    if ($packetData) {
                        $packets[] = json_decode($packetData, true);
                    } else {
                        // No more packets in queue
                        break;
                    }
                }
            } catch (\Throwable $te) {
                // Redis client not available or connection issue. Return empty set gracefully.
                Log::warning('Redis unavailable when polling packets', [
                    'error' => $te->getMessage(),
                    'session_id' => $sessionId,
                ]);
                return response()->json([
                    'success' => true,
                    'packets' => [],
                    'count' => 0,
                    'session_id' => $sessionId,
                    'fallback' => 'redis_unavailable',
                ]);
            }

            return response()->json([
                'success' => true,
                'packets' => $packets,
                'count' => count($packets),
                'session_id' => $sessionId,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found or inactive',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to poll packets', [
                'error' => $e->getMessage(),
                'session_id' => $request->input('session_id', 'unknown'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve packets',
            ], 500);
        }
    }

    /**
     * Delete a monitoring session and its packets
     */
    public function destroy(string $sessionId)
    {
        try {
            $session = LiveMonitoringSession::where('session_id', $sessionId)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            // Stop if active
            if ($session->isActive()) {
                $this->service->stopSession($session);
            }

            $session->delete();

            return response()->json([
                'success' => true,
                'message' => 'Session deleted successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete session',
            ], 500);
        }
    }
}
