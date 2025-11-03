<?php

namespace App\Http\Controllers;

use App\Models\NetworkLog;
use App\Http\Requests\StoreNetworkLogRequest;
use App\Jobs\ProcessPcapFile;
use App\Services\PcapAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

/**
 * Class NetworkLogController
 *
 * Handles CRUD operations for network log files,
 * including upload, view, update, and deletion.
 *
 * @package App\Http\Controllers
 */
class NetworkLogController extends Controller
{
    /**
     * Display a listing of network logs.
     *
     * @OA\Get(
     *     path="/network-logs",
     *     summary="List all network logs",
     *     tags={"NetworkLogs"},
     *     @OA\Response(
     *         response=200,
     *         description="Network logs retrieved successfully"
     *     )
     * )
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        // Check authorization using policy
        $this->authorize('viewAny', NetworkLog::class);
        
        $networkLogs = NetworkLog::with('user')
            ->when(Auth::user()->role !== 'Admin', function ($query) {
                return $query->where('user_id', Auth::id());
            })
            ->latest('upload_date')
            ->paginate(10);

        return Inertia::render('NetworkLogs/Index', [
            'networkLogs' => $networkLogs
        ]);
    }

    /**
     * Show the form for creating a new network log.
     *
     * @return \Inertia\Response
     */
    public function create()
    {
        // Check authorization using policy
        $this->authorize('create', NetworkLog::class);
        
        return Inertia::render('NetworkLogs/Create');
    }

    /**
     * Store a newly uploaded network log.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreNetworkLogRequest $request)
    {
        try {
            \Log::info('NetworkLog upload started', $request->all());

            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $extension = strtolower($file->getClientOriginalExtension());

            // Determine file type
            $fileType = in_array($extension, ['pcap', 'pcapng', 'cap']) ? 
                ($extension === 'pcapng' ? 'pcapng' : 'pcap') : 'csv';

            $filePath = $file->storeAs('network_logs', $fileName, 'public');

            \Log::info('File stored at: ' . $filePath);

            $networkLog = NetworkLog::create([
                'user_id'     => Auth::id(),
                'file_name'   => $file->getClientOriginalName(),
                'file_path'   => $filePath,
                'file_type'   => $fileType,
                'file_size'   => $file->getSize(),
                'upload_date' => now(),
                'status'      => 'pending',
            ]);

            \Log::info('NetworkLog created successfully', [
                'id' => $networkLog->id,
                'type' => $fileType
            ]);

            // Process based on file type
            if ($fileType === 'pcap' || $fileType === 'pcapng') {
                // Dispatch PCAP processing job
                dispatch(new ProcessPcapFile($networkLog));
                
                return redirect()->route('network-logs.index')
                    ->with('success', 'PCAP file uploaded successfully! Processing in background...');
            } else {
                // Trigger CSV/ML processing
                try {
                    \Artisan::call('ids:process-log', ['log_id' => $networkLog->id]);
                } catch (\Exception $e) {
                    \Log::warning('ML processing failed, but upload succeeded', [
                        'log_id' => $networkLog->id,
                        'error'  => $e->getMessage(),
                    ]);
                }
                
                return redirect()->route('network-logs.index')
                    ->with('success', 'Network log uploaded successfully!');
            }
        } catch (\Exception $e) {
            \Log::error('NetworkLog upload failed: ' . $e->getMessage());
            return back()->with('error', 'Upload failed: ' . $e->getMessage())
                ->withErrors(['file' => 'Upload failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified network log.
     *
     * @param NetworkLog $networkLog
     * @return \Inertia\Response
     */
    public function show(NetworkLog $networkLog)
    {
        // Check authorization using policy
        $this->authorize('view', $networkLog);
        
        $networkLog->load(['user', 'alerts.mlModel']);

        return Inertia::render('NetworkLogs/Show', [
            'networkLog' => $networkLog
        ]);
    }

    /**
     * Display the content of the specified network log file.
     *
     * @param NetworkLog $networkLog
     * @return \Inertia\Response
     */
    public function showLog(NetworkLog $networkLog)
    {
        $this->authorize('view', $networkLog);

        $logContent = Storage::disk('public')->get($networkLog->file_path);

        return Inertia::render('NetworkLogs/View', [
            'networkLog' => $networkLog,
            'logContent' => $logContent,
        ]);
    }

    /**
     * Show the form for editing the specified network log.
     *
     * @param NetworkLog $networkLog
     * @return \Inertia\Response
     */
    public function edit(NetworkLog $networkLog)
    {
        // Check authorization using policy
        $this->authorize('update', $networkLog);
        
        return Inertia::render('NetworkLogs/Edit', [
            'networkLog' => $networkLog
        ]);
    }

    /**
     * Update the specified network log status.
     *
     * @param Request $request
     * @param NetworkLog $networkLog
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, NetworkLog $networkLog)
    {
        // Check authorization using policy
        $this->authorize('update', $networkLog);
        
        $request->validate([
            'status' => 'required|in:pending,processing,processed,failed',
        ]);

        $networkLog->update($request->only('status'));

        return redirect()->route('network-logs.index')
            ->with('success', 'Network log updated successfully.');
    }

    /**
     * Remove the specified network log and its file from storage.
     *
     * @param NetworkLog $networkLog
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(NetworkLog $networkLog)
    {
        // Check authorization using policy
        $this->authorize('delete', $networkLog);
        
        // Delete the file from storage
        if ($networkLog->file_path && Storage::disk('public')->exists($networkLog->file_path)) {
            Storage::disk('public')->delete($networkLog->file_path);
        }

        $networkLog->delete();

        return redirect()->route('network-logs.index')
            ->with('success', 'Network log deleted successfully.');
    }

    /**
     * Get file preview/summary for PCAP files
     */
    public function preview(Request $request, PcapAnalysisService $service)
    {
        $request->validate([
            'file' => 'required|file|max:512000|mimes:pcap,pcapng,cap'
        ]);

        try {
            $file = $request->file('file');
            $tempPath = $file->storeAs('temp', uniqid() . '_' . $file->getClientOriginalName(), 'public');
            $fullPath = Storage::disk('public')->path($tempPath);

            // Get quick summary
            $summary = $service->getQuickSummary($fullPath);

            // Delete temp file
            Storage::disk('public')->delete($tempPath);

            return response()->json([
                'success' => true,
                'summary' => $summary
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to preview file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get processing progress for a network log
     */
    public function progress(NetworkLog $networkLog)
    {
        $this->authorize('view', $networkLog);

        return response()->json([
            'success' => true,
            'progress' => $networkLog->processing_progress,
            'status' => $networkLog->status,
            'packet_count' => $networkLog->packet_count,
            'error' => $networkLog->processing_error
        ]);
    }

    /**
     * Receive processed packets from Python script (webhook)
     */
    public function processPacket(Request $request)
    {
        try {
            $request->validate([
                'network_log_id' => 'required|integer',
                'packets' => 'required|array'
            ]);

            $networkLog = NetworkLog::findOrFail($request->network_log_id);

            // Here you would process packets and create alerts if needed
            // For now, just update packet count
            $packetCount = count($request->packets);
            $networkLog->increment('packet_count', $packetCount);

            \Log::info('Received packet batch', [
                'network_log_id' => $networkLog->id,
                'packet_count' => $packetCount
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Packets received'
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to process packet batch: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to process packets'
            ], 500);
        }
    }

    /**
     * Retry failed PCAP processing
     */
    public function retry(NetworkLog $networkLog)
    {
        $this->authorize('update', $networkLog);

        if (!$networkLog->isPcap()) {
            return back()->with('error', 'Only PCAP files can be retried');
        }

        if ($networkLog->status !== 'failed') {
            return back()->with('error', 'Only failed logs can be retried');
        }

        // Reset status and dispatch job again
        $networkLog->update([
            'status' => 'pending',
            'processing_progress' => 0,
            'processing_error' => null
        ]);

        dispatch(new ProcessPcapFile($networkLog));

        return back()->with('success', 'Processing retry initiated');
    }
}
