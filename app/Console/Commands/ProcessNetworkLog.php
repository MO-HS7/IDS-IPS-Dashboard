<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\NetworkLog;
use App\Models\Alert;
use App\Models\MLModel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProcessNetworkLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ids:process-log {log_id : The ID of the network log to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process network log file using ML models for intrusion detection';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $logId = $this->argument('log_id');
        
        $this->info("Processing network log ID: {$logId}");
        
        // Find the network log
        $networkLog = NetworkLog::find($logId);
        if (!$networkLog) {
            $this->error("Network log with ID {$logId} not found.");
            return 1;
        }
        
        // Update status to processing
        $networkLog->update(['status' => 'processing']);
        
        try {
            // Get the file path
            $filePath = storage_path('app/' . $networkLog->file_path);
            
            if (!file_exists($filePath)) {
                throw new \Exception("File not found: {$filePath}");
            }
            
            // Run ML prediction
            $results = $this->runMLPrediction($filePath);
            
            if ($results) {
                // Create alerts based on predictions
                $this->createAlertsFromPredictions($networkLog, $results);
                
                // Update status to processed
                $networkLog->update(['status' => 'processed']);
                
                $this->info("Successfully processed network log and created alerts.");
            } else {
                throw new \Exception("ML prediction failed");
            }
            
        } catch (\Exception $e) {
            // Update status to failed
            $networkLog->update(['status' => 'failed']);
            
            $this->error("Error processing network log: " . $e->getMessage());
            Log::error("Network log processing failed", [
                'log_id' => $logId,
                'error' => $e->getMessage()
            ]);
            
            return 1;
        }
        
        return 0;
    }
    
    /**
     * Run ML prediction on the network log file
     */
    private function runMLPrediction($filePath)
    {
        $this->info("Running ML prediction...");
        
        // Path to Python script
        $scriptPath = base_path('ml_scripts/predict.py');
        $modelDir = storage_path('app/models');
        $outputPath = storage_path('app/temp/prediction_results.json');
        
        // Ensure temp directory exists
        $tempDir = dirname($outputPath);
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        
        // Check if models exist, if not train them first
        if (!$this->modelsExist($modelDir)) {
            $this->info("Models not found. Training models first...");
            $this->trainModels($filePath, $modelDir);
        }
        
        // Run prediction
        $command = "cd " . base_path('ml_scripts') . " && python3 predict.py \"{$modelDir}\" \"{$filePath}\" \"{$outputPath}\" 2>&1";
        
        $this->info("Executing: {$command}");
        
        $output = shell_exec($command);
        $this->info("Python output: " . $output);
        
        // Read results
        if (file_exists($outputPath)) {
            $results = json_decode(file_get_contents($outputPath), true);
            unlink($outputPath); // Clean up temp file
            return $results;
        }
        
        return null;
    }
    
    /**
     * Check if ML models exist
     */
    private function modelsExist($modelDir)
    {
        if (!is_dir($modelDir)) {
            return false;
        }
        
        $requiredFiles = ['random_forest_ids.pkl', 'scaler.pkl', 'label_encoder.pkl'];
        
        foreach ($requiredFiles as $file) {
            if (!file_exists($modelDir . '/' . $file)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Train ML models
     */
    private function trainModels($dataPath, $modelDir)
    {
        $scriptPath = base_path('ml_scripts/train_model.py');
        
        // Ensure model directory exists
        if (!is_dir($modelDir)) {
            mkdir($modelDir, 0755, true);
        }
        
        $command = "cd " . base_path('ml_scripts') . " && python3 train_model.py \"{$dataPath}\" \"{$modelDir}\" 2>&1";
        
        $this->info("Training models: {$command}");
        
        $output = shell_exec($command);
        $this->info("Training output: " . $output);
    }
    
    /**
     * Create alerts from ML predictions
     */
    private function createAlertsFromPredictions($networkLog, $results)
    {
        $this->info("Creating alerts from predictions...");
        
        // Get or create ML model record
        $mlModel = MLModel::firstOrCreate([
            'name' => 'Random Forest IDS'
        ], [
            'description' => 'Random Forest model for intrusion detection',
            'file_path' => 'models/random_forest_ids.pkl',
            'trained_at' => now()
        ]);
        
        $alertsCreated = 0;
        
        // Process results from the best performing model (random_forest)
        if (isset($results['results']['random_forest'])) {
            $predictions = $results['results']['random_forest'];
            
            foreach ($predictions as $prediction) {
                // Only create alerts for attacks (not normal traffic)
                if ($prediction['is_attack'] && $prediction['confidence'] > 0.5) {
                    
                    // Map prediction to attack types
                    $attackType = $this->mapPredictionToAttackType($prediction['prediction']);
                    
                    Alert::create([
                        'network_log_id' => $networkLog->id,
                        'ml_model_id' => $mlModel->id,
                        'attack_type' => $attackType,
                        'severity' => $prediction['severity'],
                        'detected_at' => now(),
                        'description' => "Detected {$attackType} attack with {$prediction['confidence']} confidence using Random Forest model."
                    ]);
                    
                    $alertsCreated++;
                }
            }
        }
        
        $this->info("Created {$alertsCreated} alerts.");
    }
    
    /**
     * Map ML prediction to attack type
     */
    private function mapPredictionToAttackType($prediction)
    {
        $mapping = [
            'dos' => 'DDoS',
            'ddos' => 'DDoS',
            'probe' => 'Port Scan',
            'r2l' => 'Remote to Local',
            'u2r' => 'User to Root',
            'normal' => 'Normal',
            'brute_force' => 'Brute Force',
            'sql_injection' => 'SQL Injection',
            'xss' => 'XSS',
            'port_scan' => 'Port Scan',
            'malware' => 'Malware',
            'phishing' => 'Phishing'
        ];
        
        return $mapping[strtolower($prediction)] ?? ucfirst($prediction);
    }
}
