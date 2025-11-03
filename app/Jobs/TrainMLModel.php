<?php

namespace App\Jobs;

use App\Models\MLTrainingSession;
use App\Services\MLTrainingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TrainMLModel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1 hour timeout
    public $tries = 1; // Only try once

    /**
     * Create a new job instance.
     */
    public function __construct(
        public MLTrainingSession $session
    ) {}

    /**
     * Execute the job.
     */
    public function handle(MLTrainingService $service): void
    {
        Log::info('Starting ML training job', [
            'session_id' => $this->session->session_id,
            'model_id' => $this->session->ml_model_id,
        ]);

        $service->executeTraining($this->session);

        Log::info('ML training job completed', [
            'session_id' => $this->session->session_id,
            'status' => $this->session->status,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ML training job failed', [
            'session_id' => $this->session->session_id,
            'error' => $exception->getMessage(),
        ]);

        $this->session->markAsFailed($exception->getMessage());
        $this->session->mlModel->update(['status' => 'failed']);
    }
}
