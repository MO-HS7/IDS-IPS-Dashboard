<?php

namespace App\Jobs;

use App\Models\LiveMonitoringSession;
use App\Services\LiveMonitoringService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class LiveNetworkCapture implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1 hour timeout
    public $tries = 1; // Don't retry

    protected $session;
    protected $pythonScript;

    /**
     * Create a new job instance.
     */
    public function __construct(LiveMonitoringSession $session)
    {
        $this->session = $session;
        $this->pythonScript = base_path('ml_scripts/live_capture.py');
    }

    /**
     * Execute the job.
     */
    public function handle(LiveMonitoringService $service): void
    {
        try {
            Log::info('Starting live capture job', [
                'session_id' => $this->session->session_id,
                'interface' => $this->session->interface,
            ]);

            // Check if Python script exists
            if (!file_exists($this->pythonScript)) {
                throw new \Exception('Python capture script not found: ' . $this->pythonScript);
            }

            // Get Python executable path
            $pythonPath = $this->getPythonPath();

            // Prepare API URL and Token
            $apiUrl = config('app.url') . '/api/live-monitoring/packet';
            $apiToken = $this->generateApiToken();

            // Add Reverb/Pusher configuration for local development
            $pusherConfig = [
                'PUSHER_APP_KEY' => config('broadcasting.connections.reverb.key'),
                'PUSHER_HOST' => config('broadcasting.connections.reverb.host'),
                'PUSHER_PORT' => config('broadcasting.connections.reverb.port'),
                'PUSHER_SCHEME' => config('broadcasting.connections.reverb.scheme', 'http'),
            ];

            // Prepare command
            $command = sprintf(
                '%s "%s" --session-id "%s" --interface "%s" --api-url "%s" --api-token "%s"',
                $pythonPath,
                $this->pythonScript,
                $this->session->session_id,
                $this->session->interface,
                $apiUrl,
                $apiToken
            );

            Log::info('Executing capture command', ['command' => $command]);

            // Execute Python script
            // Note: This is a long-running process
            // Execute Python script
            // Note: This is a long-running process
            $result = Process::timeout(3600)->withEnvironment($pusherConfig)->run($command);

            if (!$result->successful()) {
                throw new \Exception('Capture script failed: ' . $result->errorOutput());
            }

            Log::info('Capture script completed successfully', [
                'session_id' => $this->session->session_id,
            ]);

        } catch (\Exception $e) {
            Log::error('Live capture failed', [
                'session_id' => $this->session->session_id,
                'error' => $e->getMessage(),
            ]);

            $this->session->markAsError($e->getMessage());
            
            throw $e;
        }
    }

    /**
     * Get Python executable path
     */
    protected function getPythonPath(): string
    {
        // 1. ابحث عن مسار Python المحدد في ملف .env عبر config
        $configuredPath = config('app.python_path');

        // 2. إذا لم يكن المسار محدداً، استخدم القيمة الافتراضية المناسبة لنظام التشغيل
        if (empty($configuredPath) || $configuredPath === 'python') {
            return PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3';
        }

        // 3. استخدم المسار المحدد من ملف الإعدادات
        return $configuredPath;
        
        /* Old venv code - disabled
        $venvPath = base_path('ml_scripts/venv');
        
        if (PHP_OS_FAMILY === 'Windows') {
            $pythonExe = $venvPath . '/Scripts/python.exe';
            if (file_exists($pythonExe)) {
                return $pythonExe;
            }
            return 'python';
        } else {
            $pythonExe = $venvPath . '/bin/python';
            if (file_exists($pythonExe)) {
                return $pythonExe;
            }
            return 'python3';
        }
        */
    }

    /**
     * Generate temporary API token for Python script
     */
    protected function generateApiToken(): string
    {
        // In production, this should be a proper API token
        // For now, we'll use a simple hash
        return hash_hmac(
            'sha256',
            $this->session->session_id,
            config('app.key')
        );
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Live capture job failed permanently', [
            'session_id' => $this->session->session_id,
            'error' => $exception->getMessage(),
        ]);

        $this->session->markAsError($exception->getMessage());
    }
}
