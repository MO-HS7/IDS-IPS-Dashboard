<?php

namespace App\Services;

use App\Models\LiveMonitoringSession;
use App\Models\LiveCapturedPacket;
use App\Models\User;
use App\Events\LiveNetworkDataEvent;
use App\Events\LiveMonitoringStatusEvent;
use App\Events\ThreatDetectedEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class LiveMonitoringService
{
    /**
     * Get available network interfaces
     */
    public function getAvailableInterfaces(): array
    {
        try {
            // Use Python/Scapy to get actual Npcap device names
            $pythonScript = base_path('ml_scripts/get_interfaces.py');
            
            // If Python script doesn't exist, create it
            if (!file_exists($pythonScript)) {
                $this->createGetInterfacesScript($pythonScript);
            }
            
            // Add timeout to prevent page hanging
            $result = Process::timeout(5)->run('python "' . $pythonScript . '"');
            
            if ($result->successful()) {
                $interfaces = json_decode($result->output(), true);
                if (is_array($interfaces) && !empty($interfaces)) {
                    return $interfaces;
                }
            }
            
            // Fallback to old method
            if (PHP_OS_FAMILY === 'Windows') {
                $result = Process::timeout(3)->run('ipconfig');
                return $this->parseWindowsInterfaces($result->output());
            } else {
                $result = Process::timeout(3)->run('ip link show');
                return $this->parseLinuxInterfaces($result->output());
            }
        } catch (\Exception $e) {
            Log::error('Failed to get network interfaces: ' . $e->getMessage());
            return [
                ['name' => 'eth0', 'description' => 'Default Ethernet'],
                ['name' => 'wlan0', 'description' => 'Default WiFi'],
            ];
        }
    }
    
    /**
     * Create Python script to get interfaces
     */
    private function createGetInterfacesScript(string $path): void
    {
        $content = <<<'PYTHON'
#!/usr/bin/env python3
import json
import sys
try:
    from scapy.all import get_if_list, conf
    interfaces = []
    for iface in get_if_list():
        # Skip loopback
        if 'Loopback' not in iface:
            # Extract friendly name from device name
            name = iface
            if '\\Device\\NPF_{' in iface:
                # Try to get a friendlier description
                name = iface.split('\\Device\\NPF_')[1] if '\\Device\\NPF_' in iface else iface
            
            interfaces.append({
                'name': iface,  # Full device name for Scapy
                'description': f'Network Interface {name[:8]}...' if len(name) > 20 else name
            })
    
    print(json.dumps(interfaces))
except Exception as e:
    # Fallback
    print(json.dumps([
        {'name': conf.iface, 'description': 'Default Interface'}
    ]))
    sys.exit(0)
PYTHON;
        
        file_put_contents($path, $content);
    }

    /**
     * Parse Windows ipconfig output
     */
    private function parseWindowsInterfaces(string $output): array
    {
        $interfaces = [];
        $lines = explode("\n", $output);
        $currentInterface = null;

        foreach ($lines as $line) {
            $line = trim($line);
            
            if (str_contains($line, 'adapter')) {
                preg_match('/adapter\s+(.+?):/i', $line, $matches);
                if (isset($matches[1])) {
                    $name = trim($matches[1]);
                    $interfaces[] = [
                        'name' => $name,
                        'description' => $name,
                    ];
                }
            }
        }

        return !empty($interfaces) ? $interfaces : [
            ['name' => 'Ethernet', 'description' => 'Ethernet Adapter'],
            ['name' => 'Wi-Fi', 'description' => 'Wireless Adapter'],
        ];
    }

    /**
     * Parse Linux ip link output
     */
    private function parseLinuxInterfaces(string $output): array
    {
        $interfaces = [];
        preg_match_all('/\d+:\s+(\w+):/m', $output, $matches);

        if (isset($matches[1])) {
            foreach ($matches[1] as $interface) {
                if ($interface !== 'lo') { // Skip loopback
                    $interfaces[] = [
                        'name' => $interface,
                        'description' => ucfirst($interface) . ' Interface',
                    ];
                }
            }
        }

        return !empty($interfaces) ? $interfaces : [
            ['name' => 'eth0', 'description' => 'Ethernet Interface'],
            ['name' => 'wlan0', 'description' => 'WiFi Interface'],
        ];
    }

    /**
     * Start a new live monitoring session
     */
    public function startSession(User $user, string $interface): LiveMonitoringSession
    {
        // Stop any existing active sessions for this user
        $this->stopUserActiveSessions($user);

        // Create new session
        $session = LiveMonitoringSession::create([
            'user_id' => $user->id,
            'interface' => $interface,
            'status' => 'active',
            'started_at' => now(),
        ]);

        // Broadcast status
        broadcast(new LiveMonitoringStatusEvent(
            $user->id,
            $session->session_id,
            'started',
            'Live monitoring started on interface: ' . $interface
        ));

        Log::info('Live monitoring session started', [
            'user_id' => $user->id,
            'session_id' => $session->session_id,
            'interface' => $interface,
        ]);

        return $session;
    }

    /**
     * Stop a monitoring session
     */
    public function stopSession(LiveMonitoringSession $session): void
    {
        $session->stop();

        broadcast(new LiveMonitoringStatusEvent(
            $session->user_id,
            $session->session_id,
            'stopped',
            'Live monitoring stopped'
        ));

        Log::info('Live monitoring session stopped', [
            'session_id' => $session->session_id,
            'packets_captured' => $session->packets_captured,
            'threats_detected' => $session->threats_detected,
        ]);
    }

    /**
     * Stop all active sessions for a user
     */
    public function stopUserActiveSessions(User $user): void
    {
        $activeSessions = LiveMonitoringSession::where('user_id', $user->id)
            ->where('status', 'active')
            ->get();

        foreach ($activeSessions as $session) {
            $this->stopSession($session);
        }
    }

    /**
     * Process and store captured packet
     */
    public function processPacket(LiveMonitoringSession $session, array $packetData): LiveCapturedPacket
    {
        // Create packet record
        $packet = LiveCapturedPacket::create([
            'session_id' => $session->id,
            'captured_at' => now(),
            'source_ip' => $packetData['source_ip'] ?? 'unknown',
            'destination_ip' => $packetData['destination_ip'] ?? 'unknown',
            'source_port' => $packetData['source_port'] ?? null,
            'destination_port' => $packetData['destination_port'] ?? null,
            'protocol' => $packetData['protocol'] ?? 'unknown',
            'packet_length' => $packetData['length'] ?? 0,
            'payload_preview' => $packetData['payload'] ?? null,
            'flags' => $packetData['flags'] ?? null,
            'threat_score' => $packetData['threat_score'] ?? 0.0,
            'is_threat' => ($packetData['threat_score'] ?? 0.0) > 0.7,
            'threat_type' => $packetData['threat_type'] ?? null,
            'raw_data' => $packetData,
        ]);

        // Update session statistics
        $session->incrementPacketCount();
        
        if ($packet->is_threat) {
            $session->incrementThreatCount();
            
            // Broadcast threat alert
            broadcast(new ThreatDetectedEvent(
                $session->user_id,
                $session->session_id,
                $packet->getFormattedInfo()
            ));
        }

        // Calculate and update statistics
        $statistics = $this->calculateSessionStatistics($session);
        $session->update(['statistics' => $statistics]);

        // Broadcast packet data
        broadcast(new LiveNetworkDataEvent(
            $session->user_id,
            $session->session_id,
            $packet->getFormattedInfo(),
            $statistics
        ));

        return $packet;
    }

    /**
     * Calculate session statistics
     */
    public function calculateSessionStatistics(LiveMonitoringSession $session): array
    {
        $packets = $session->packets()
            ->where('captured_at', '>=', now()->subMinutes(5))
            ->get();

        if ($packets->isEmpty()) {
            return [
                'packets_per_second' => 0,
                'bytes_per_second' => 0,
                'protocols' => [],
                'top_sources' => [],
                'top_destinations' => [],
                'threat_percentage' => 0,
            ];
        }

        $duration = $packets->first()->captured_at->diffInSeconds($packets->last()->captured_at);
        $duration = max($duration, 1); // Prevent division by zero

        $protocolCounts = $packets->groupBy('protocol')->map->count()->toArray();
        $sourceCounts = $packets->groupBy('source_ip')->map->count()->sortDesc()->take(5)->toArray();
        $destCounts = $packets->groupBy('destination_ip')->map->count()->sortDesc()->take(5)->toArray();

        $totalBytes = $packets->sum('packet_length');
        $threatCount = $packets->where('is_threat', true)->count();

        return [
            'packets_per_second' => round($packets->count() / $duration, 2),
            'bytes_per_second' => round($totalBytes / $duration, 2),
            'protocols' => $protocolCounts,
            'top_sources' => $sourceCounts,
            'top_destinations' => $destCounts,
            'threat_percentage' => $packets->count() > 0 
                ? round(($threatCount / $packets->count()) * 100, 2) 
                : 0,
        ];
    }

    /**
     * Get session statistics
     */
    public function getSessionStatistics(LiveMonitoringSession $session): array
    {
        return [
            'session_id' => $session->session_id,
            'interface' => $session->interface,
            'status' => $session->status,
            'started_at' => $session->started_at->toIso8601String(),
            'duration' => $session->started_at->diffInSeconds(now()),
            'packets_captured' => $session->packets_captured,
            'threats_detected' => $session->threats_detected,
            'statistics' => $session->statistics ?? [],
        ];
    }

    /**
     * Get recent packets for a session
     */
    public function getRecentPackets(LiveMonitoringSession $session, int $limit = 100): array
    {
        return $session->packets()
            ->latest('captured_at')
            ->limit($limit)
            ->get()
            ->map(fn($packet) => $packet->getFormattedInfo())
            ->toArray();
    }
}
