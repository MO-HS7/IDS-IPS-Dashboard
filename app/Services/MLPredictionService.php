<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Exception;

class MLPredictionService
{
    protected string $pythonPath;
    protected string $scriptPath;
    protected int $timeout;

    public function __construct()
    {
        // Configure paths
        $this->pythonPath = config('ml.python_path', 'python');
        $this->scriptPath = base_path('ml_scripts/predict_packet.py');
        $this->timeout = config('ml.prediction_timeout', 30);
    }

    /**
     * Predict if a packet is malicious
     *
     * @param array $packetData
     * @return array
     */
    public function predictPacket(array $packetData): array
    {
        try {
            // Validate packet data
            if (empty($packetData)) {
                throw new Exception('Packet data is empty');
            }

            // Convert packet data to JSON
            $packetJson = json_encode($packetData);

            // Execute Python script
            $result = Process::timeout($this->timeout)
                ->run([
                    $this->pythonPath,
                    $this->scriptPath,
                    $packetJson
                ]);

            if ($result->failed()) {
                Log::error('ML prediction failed', [
                    'error' => $result->errorOutput(),
                    'exit_code' => $result->exitCode()
                ]);

                return $this->getDefaultPrediction('error');
            }

            // Parse output
            $output = trim($result->output());
            $prediction = json_decode($output, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Failed to parse ML prediction output', [
                    'output' => $output,
                    'error' => json_last_error_msg()
                ]);

                return $this->getDefaultPrediction('parse_error');
            }

            return $prediction;

        } catch (Exception $e) {
            Log::error('ML prediction exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->getDefaultPrediction('exception');
        }
    }

    /**
     * Predict multiple packets in batch
     *
     * @param array $packetsData
     * @return array
     */
    public function predictBatch(array $packetsData): array
    {
        try {
            if (empty($packetsData)) {
                return [];
            }

            // Convert to JSON
            $packetsJson = json_encode($packetsData);

            // Execute Python script
            $result = Process::timeout($this->timeout * 2)
                ->run([
                    $this->pythonPath,
                    $this->scriptPath,
                    $packetsJson
                ]);

            if ($result->failed()) {
                Log::error('ML batch prediction failed', [
                    'error' => $result->errorOutput()
                ]);

                return array_map(
                    fn() => $this->getDefaultPrediction('error'),
                    $packetsData
                );
            }

            $output = trim($result->output());
            $predictions = json_decode($output, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return array_map(
                    fn() => $this->getDefaultPrediction('parse_error'),
                    $packetsData
                );
            }

            return $predictions;

        } catch (Exception $e) {
            Log::error('ML batch prediction exception', [
                'message' => $e->getMessage()
            ]);

            return array_map(
                fn() => $this->getDefaultPrediction('exception'),
                $packetsData
            );
        }
    }

    /**
     * Get default prediction for error cases
     *
     * @param string $errorType
     * @return array
     */
    protected function getDefaultPrediction(string $errorType): array
    {
        return [
            'prediction' => 'unknown',
            'is_attack' => false,
            'confidence' => 0,
            'severity' => 'info',
            'severity_score' => 0,
            'error' => $errorType,
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * Check if ML service is available
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        try {
            $testData = [
                'length' => 100,
                'protocol' => 6,
                'src_port' => 443,
                'dst_port' => 8080
            ];

            $result = $this->predictPacket($testData);

            return !isset($result['error']);

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get model information
     *
     * @return array
     */
    public function getModelInfo(): array
    {
        $metadataPath = base_path('ml_models/model_metadata.json');

        if (!file_exists($metadataPath)) {
            return [
                'available' => false,
                'message' => 'Model metadata not found'
            ];
        }

        try {
            $metadata = json_decode(file_get_contents($metadataPath), true);

            return [
                'available' => true,
                'model_type' => $metadata['model_type'] ?? 'unknown',
                'accuracy' => $metadata['accuracy'] ?? 0,
                'training_date' => $metadata['training_date'] ?? null,
                'classes' => $metadata['classes'] ?? [],
                'n_features' => $metadata['n_features'] ?? 0
            ];

        } catch (Exception $e) {
            return [
                'available' => false,
                'message' => 'Failed to read model metadata'
            ];
        }
    }
}
