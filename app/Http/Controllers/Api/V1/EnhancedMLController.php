<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LiveMonitoringSession;
use App\Models\NetworkLog;
use App\Models\Alert;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;

class EnhancedMLController extends Controller
{
    protected $mlScriptsPath;
    protected $pythonPath;

    public function __construct()
    {
        $this->mlScriptsPath = base_path('ml_scripts');
        $this->pythonPath = env('PYTHON_PATH', 'python3');
    }

    /**
     * Start ML-enhanced real-time monitoring
     * Implements dual verification as per research paper
     */
    public function startEnhancedMonitoring(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'interface' => 'required|string',
                'enable_snort' => 'boolean',
                'ml_model' => 'string|nullable',
                'snort_rules' => 'string|nullable'
            ]);

            $sessionId = $this->generateSessionId();
            
            // Create Laravel session record
            $session = LiveMonitoringSession::create([
                'session_id' => $sessionId,
                'interface' => $validated['interface'],
                'status' => 'starting',
                'ml_enabled' => true,
                'snort_enabled' => $validated['enable_snort'] ?? false,
                'configuration' => [
                    'ml_model_path' => $validated['ml_model'],
                    'snort_rules_path' => $validated['snort_rules'],
                    'hybrid_detection' => true,
                    'dual_verification' => true
                ]
            ]);

            // Start enhanced pipeline
            $command = $this->buildEnhancedPipelineCommand($sessionId, $validated);
            
            $process = Process::timeout(0)->start($command, function (string $type, string $output) use ($sessionId) {
                Log::info("Enhanced pipeline output", ['session_id' => $sessionId, 'type' => $type, 'output' => $output]);
                $this->processPipelineOutput($sessionId, $type, $output);
            });

            // Update session status
            $session->update([
                'status' => 'running',
                'process_id' => $process->pid(),
                'started_at' => now()
            ]);

            // Broadcast real-time session start
            event(new \App\Events\MonitoringSessionStarted($session));

            return response()->json([
                'success' => true,
                'session_id' => $sessionId,
                'message' => 'Enhanced ML monitoring started successfully',
                'configuration' => [
                    'hybrid_detection' => true,
                    'ml_prediction' => true,
                    'snort_validation' => $validated['enable_snort'] ?? false,
                    'dual_verification' => true,
                    'flow_based_analysis' => true
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Enhanced monitoring startup failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to start enhanced monitoring: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process ML detection results
     * Implements dual verification results handling
     */
    public function processDetectionResults(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'session_id' => 'required|string',
                'classification' => 'required|in:normal,attack',
                'attack_type' => 'string|nullable',
                'confidence' => 'required|numeric|min:0|max:1',
                'confidence_level' => 'required|in:low,medium,high',
                'ml_result' => 'array',
                'snort_result' => 'array',
                'flow_key' => 'string|nullable',
                'src_ip' => 'string|nullable',
                'dst_ip' => 'string|nullable'
            ]);

            // Create alert for attack classifications
            if ($validated['classification'] === 'attack') {
                $alert = Alert::create([
                    'session_id' => $validated['session_id'],
                    'alert_type' => 'ml_detection',
                    'severity' => $this->calculateSeverity($validated['confidence_level'], $validated['attack_type']),
                    'title' => "ML-Enhanced Attack Detection: {$validated['attack_type']}",
                    'description' => $this->generateAttackDescription($validated),
                    'source_ip' => $validated['src_ip'],
                    'destination_ip' => $validated['dst_ip'],
                    'classification' => $validated['classification'],
                    'attack_type' => $validated['attack_type'],
                    'confidence_score' => $validated['confidence'],
                    'details' => [
                        'ml_prediction' => $validated['ml_result'] ?? null,
                        'snort_validation' => $validated['snort_result'] ?? null,
                        'detection_method' => 'hybrid_ml_snort',
                        'dual_verification' => true
                    ],
                    'status' => 'active'
                ]);

                // Trigger real-time alert
                event(new \App\Events\ThreatDetected($alert));

                // Auto-response for high-confidence attacks
                if ($validated['confidence_level'] === 'high' && $validated['src_ip']) {
                    $this->triggerAutoResponse($validated);
                }
            }

            // Update network log with ML classification
            NetworkLog::updateOrCreate(
                ['session_id' => $validated['session_id'], 'flow_key' => $validated['flow_key']],
                [
                    'classification' => $validated['classification'],
                    'attack_type' => $validated['attack_type'],
                    'confidence_score' => $validated['confidence'],
                    'ml_processed' => true,
                    'processed_at' => now()
                ]
            );

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Detection results processing failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false], 500);
        }
    }

    /**
     * Get ML model training status and metrics
     */
    public function modelTrainingStatus(): JsonResponse
    {
        try {
            $trainingLogsPath = storage_path('app/ml_training.log');
            $modelMetricsPath = storage_path('app/model_metrics.json');
            
            $status = [
                'models_available' => $this->getAvailableModels(),
                'training_in_progress' => $this->checkTrainingProgress(),
                'last_training' => $this->getLastTrainingDate(),
                'dataset_info' => $this->getDatasetInfo()
            ];

            if (file_exists($modelMetricsPath)) {
                $status['metrics'] = json_decode(file_get_contents($modelMetricsPath), true);
            }

            return response()->json($status);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Start ML model training with research paper methodology
     */
    public function startModelTraining(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'dataset_path' => 'required|string',
                'model_type' => 'required|in:random_forest,neural_network,svm,decision_tree,naive_bayes',
                'test_size' => 'numeric|min:0.1|max:0.5',
                'random_state' => 'integer'
            ]);

            $trainingCommand = sprintf(
                '%s %s/train_model.py "%s" "%s" --model-type %s --test-size %s --random-state %s',
                $this->pythonPath,
                $this->mlScriptsPath,
                $validated['dataset_path'],
                storage_path('app/ml_models'),
                $validated['model_type'],
                $validated['test_size'] ?? 0.2,
                $validated['random_state'] ?? 42
            );

            $process = Process::start($trainingCommand, function ($type, $output) {
                if ($type === 'out') {
                    Log::info('ML Training: ' . $output);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Model training started',
                'process_id' => $process->pid(),
                'command' => $trainingCommand
            ]);

        } catch (\Exception $e) {
            Log::error('Model training failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get hybrid detection analytics
     */
    public function hybridDetectionAnalytics(Request $request): JsonResponse
    {
        try {
            $days = $request->get('days', 7);
            $startDate = now()->subDays($days);

            $analytics = [
                'ml_accuracy' => $this->getMLAccuracyStats($startDate),
                'snort_validation' => $this->getSnortValidationStats($startDate),
                'hybrid_efficiency' => $this->getHybridEfficiencyStats($startDate),
                'attack_classification' => $this->getAttackClassificationStats($startDate),
                'false_positive_analysis' => $this->getFalsePositiveAnalysis($startDate)
            ];

            return response()->json($analytics);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update Snort rules for validation
     */
    public function updateSnortRules(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'rules_content' => 'required|string',
                'source_url' => 'url|nullable'
            ]);

            $rulesPath = storage_path('app/snort_rules.txt');
            
            // Backup existing rules
            if (file_exists($rulesPath)) {
                $backupPath = $rulesPath . '.backup.' . date('Y-m-d_H-i-s');
                copy($rulesPath, $backupPath);
            }

            // Write new rules
            file_put_contents($rulesPath, $validated['rules_content']);

            // Optional: Update from external source
            if ($validated['source_url']) {
                $this->updateFromSnortRepo($validated['source_url']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Snort rules updated successfully',
                'rules_count' => count(explode("\n", $validated['rules_content']))
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Helper methods

    private function buildEnhancedPipelineCommand(string $sessionId, array $validated): string
    {
        $command = sprintf(
            '%s %s/enhanced_pipeline.py --session-id %s --interface %s',
            $this->pythonPath,
            $this->mlScriptsPath,
            $sessionId,
            $validated['interface']
        );

        if (isset($validated['ml_model']) && $validated['ml_model']) {
            $command .= sprintf(' --model-path "%s"', $validated['ml_model']);
        }

        if (isset($validated['snort_rules']) && $validated['snort_rules']) {
            $command .= sprintf(' --snort-rules "%s"', $validated['snort_rules']);
        }

        if (env('TSHARK_PATH')) {
            $command .= sprintf(' --tshark-path "%s"', env('TSHARK_PATH'));
        }

        return $command;
    }

    private function processPipelineOutput(string $sessionId, string $type, string $output): void
    {
        if ($type === 'out' && str_contains($output, '[ATTACK DETECTED]')) {
            // Parse attack detection output and trigger Laravel events
            preg_match('/\[ATTACK DETECTED\] (.+) from (.+) to (.+)/', $output, $matches);
            
            if (count($matches) >= 4) {
                $this->createAlertFromOutput($sessionId, $matches);
            }
        }
    }

    private function createAlertFromOutput(string $sessionId, array $matches): void
    {
        $alert = Alert::create([
            'session_id' => $sessionId,
            'alert_type' => 'ml_detection',
            'severity' => 'high',
            'title' => $matches[1],
            'description' => "Real-time attack detection via enhanced ML pipeline",
            'source_ip' => $matches[2],
            'destination_ip' => $matches[3],
            'classification' => 'attack',
            'details' => [
                'detection_method' => 'enhanced_pipeline',
                'real_time' => true
            ]
        ]);

        event(new \App\Events\ThreatDetected($alert));
    }

    private function triggerAutoResponse(array $validated): void
    {
        // Implement automated response (firewall blocking, etc.)
        Log::info('Auto-response triggered', [
            'source_ip' => $validated['src_ip'],
            'attack_type' => $validated['attack_type'],
            'confidence' => $validated['confidence']
        ]);

        // Example: Trigger IP blocking
        if (env('AUTO_RESPONSE_ENABLED', false)) {
            Artisan::call('firewall:block-ip', ['ip' => $validated['src_ip']]);
        }
    }

    private function generateSessionId(): string
    {
        return 'ml_' . uniqid() . '_' . bin2hex(random_bytes(8));
    }

    private function calculateSeverity(string $confidenceLevel, ?string $attackType): string
    {
        if ($attackType === 'DoS' || $attackType === 'DDoS') {
            return $confidenceLevel === 'high' ? 'critical' : 'high';
        }
        if ($confidenceLevel === 'high') {
            return 'high';
        }
        if ($confidenceLevel === 'medium') {
            return 'medium';
        }
        return 'low';
    }

    private function generateAttackDescription(array $validated): string
    {
        $description = "Hybrid ML-Snort detection: ";
        $description .= $validated['attack_type'] ?? 'Unknown attack type';
        $description .= sprintf(" (Confidence: %s - %.1f%%)", 
            $validated['confidence_level'], 
            $validated['confidence'] * 100);
        
        if ($validated['src_ip']) {
            $description .= sprintf(" from %s", $validated['src_ip']);
        }
        
        if ($validated['dst_ip']) {
            $description .= sprintf(" to %s", $validated['dst_ip']);
        }

        return $description;
    }

    // Analytics helper methods
    private function getMLAccuracyStats($startDate): array
    {
        return Alert::where('created_at', '>=', $startDate)
            ->where('alert_type', 'ml_detection')
            ->selectRaw('AVG(confidence_score) as avg_confidence, 
                        COUNT(*) as total_detections,
                        COUNT(CASE WHEN confidence_level = "high" THEN 1 END) as high_confidence_detections')
            ->first()
            ->toArray();
    }

    private function getSnortValidationStats($startDate): array
    {
        // This would analyze Snort validation data
        return [
            'rules_matched' => 0, // Placeholder
            'validation_accuracy' => 0, // Placeholder
            'cross_validation_rate' => 0 // Placeholder
        ];
    }

    private function getHybridEfficiencyStats($startDate): array
    {
        // Analyze ML + Snort hybrid detection efficiency
        return [
            'dual_verification_rate' => 0, // Placeholder
            'ml_only_detections' => 0, // Placeholder
            'snort_only_detections' => 0, // Placeholder
            'combined_effectiveness' => 0 // Placeholder
        ];
    }

    private function getAttackClassificationStats($startDate): array
    {
        return Alert::where('created_at', '>=', $startDate)
            ->where('alert_type', 'ml_detection')
            ->where('classification', 'attack')
            ->selectRaw('attack_type, COUNT(*) as count')
            ->groupBy('attack_type')
            ->pluck('count', 'attack_type')
            ->toArray();
    }

    private function getFalsePositiveAnalysis($startDate): array
    {
        // Analyze false positive rates
        return [
            'total_alerts' => Alert::where('created_at', '>=', $startDate)->count(),
            'confirmed_threats' => 0, // Placeholder
            'false_positives' => 0, // Placeholder
            'false_positive_rate' => 0 // Placeholder
        ];
    }

    private function getAvailableModels(): array
    {
        $modelPath = storage_path('app/ml_models');
        if (!is_dir($modelPath)) {
            return [];
        }

        $models = [];
        foreach (glob($modelPath . '/*_model.pkl') as $modelFile) {
            $models[] = basename($modelFile);
        }

        return $models;
    }

    private function checkTrainingProgress(): bool
    {
        // Check if training process is running
        return Process::running()->isNotEmpty();
    }

    private function getLastTrainingDate(): ?string
    {
        $metricsPath = storage_path('app/model_metrics.json');
        if (!file_exists($metricsPath)) {
            return null;
        }

        $metrics = json_decode(file_get_contents($metricsPath), true);
        return $metrics['last_training'] ?? null;
    }

    private function getDatasetInfo(): array
    {
        return [
            'total_samples' => 0, // Placeholder
            'feature_count' => 0, // Placeholder
            'attack_types' => ['DoS', 'Probe', 'R2L', 'U2R'], // From research paper
            'normal_traffic_ratio' => 0.6 // From research paper
        ];
    }

    private function updateFromSnortRepo(string $sourceUrl): void
    {
        try {
            $response = Http::get($sourceUrl);
            if ($response->successful()) {
                $rulesPath = storage_path('app/snort_rules.txt');
                file_put_contents($rulesPath, $response->body());
            }
        } catch (\Exception $e) {
            Log::warning('Failed to update Snort rules from source', ['error' => $e->getMessage()]);
        }
    }
}