<?php

namespace App\Jobs;

use App\Models\NetworkLog;
use App\Services\PcapAnalysisService;
use App\Events\NetworkLogProcessed;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessPcapFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes timeout
    public $tries = 1; // Don't retry PCAP processing

    protected $networkLog;

    /**
     * Create a new job instance.
     */
    public function __construct(NetworkLog $networkLog)
    {
        $this->networkLog = $networkLog;
    }

    /**
     * Execute the job.
     */
    public function handle(PcapAnalysisService $service): void
    {
        try {
            Log::info('Starting PCAP file processing', [
                'network_log_id' => $this->networkLog->id,
                'file_name' => $this->networkLog->file_name
            ]);

            // Update status to processing
            $this->networkLog->update([
                'status' => 'processing',
                'processing_progress' => 0
            ]);

            // Process the PCAP file with progress callback
            $success = $service->processPcapFile(
                $this->networkLog,
                function ($progress, $message) {
                    $this->networkLog->updateProgress($progress, 'processing');
                    
                    Log::info('PCAP processing progress', [
                        'network_log_id' => $this->networkLog->id,
                        'progress' => $progress,
                        'message' => $message
                    ]);
                }
            );

            if ($success) {
                // Broadcast completion event
                broadcast(new NetworkLogProcessed($this->networkLog));

                Log::info('PCAP file processed successfully', [
                    'network_log_id' => $this->networkLog->id,
                    'packet_count' => $this->networkLog->packet_count
                ]);
            } else {
                throw new \Exception('PCAP processing returned false');
            }

        } catch (\Exception $e) {
            Log::error('PCAP file processing failed', [
                'network_log_id' => $this->networkLog->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->networkLog->markAsFailed($e->getMessage());
            
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('PCAP processing job failed permanently', [
            'network_log_id' => $this->networkLog->id,
            'error' => $exception->getMessage()
        ]);

        $this->networkLog->markAsFailed(
            'Processing failed: ' . $exception->getMessage()
        );
    }
}
