<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MLModel;
use App\Models\MLModelMetric;
use App\Models\MLTrainingSession;
use Illuminate\Support\Str;

class MLMetricsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first 3 models
        $models = MLModel::take(3)->get();

        if ($models->isEmpty()) {
            $this->command->info('No ML models found. Please create models first.');
            return;
        }

        foreach ($models as $index => $model) {
            // Create training session
            $session = MLTrainingSession::create([
                'ml_model_id' => $model->id,
                'user_id' => 1, // Assuming admin user
                'session_id' => Str::uuid(),
                'status' => 'completed',
                'model_type' => ['random_forest', 'neural_network', 'svm'][$index % 3],
                'hyperparameters' => [
                    'n_estimators' => 100,
                    'max_depth' => null,
                ],
                'dataset_path' => 'sample_data',
                'dataset_size' => 10000,
                'progress' => 100,
                'status_message' => 'Training completed successfully',
                'accuracy' => 0.9200 + ($index * 0.01),
                'precision' => 0.8900 + ($index * 0.01),
                'recall' => 0.8700 + ($index * 0.01),
                'f1_score' => 0.8800 + ($index * 0.01),
                'started_at' => now()->subHours(2),
                'completed_at' => now()->subHours(1),
                'duration_seconds' => 3600,
            ]);

            // Create metrics
            MLModelMetric::create([
                'ml_model_id' => $model->id,
                'training_session_id' => $session->id,
                'accuracy' => 0.9200 + ($index * 0.01),
                'precision' => 0.8900 + ($index * 0.01),
                'recall' => 0.8700 + ($index * 0.01),
                'f1_score' => 0.8800 + ($index * 0.01),
                'confusion_matrix' => [
                    [850, 50],
                    [80, 920]
                ],
                'classification_report' => [
                    'normal' => [
                        'precision' => 0.91,
                        'recall' => 0.94,
                        'f1-score' => 0.92,
                    ],
                    'attack' => [
                        'precision' => 0.95,
                        'recall' => 0.92,
                        'f1-score' => 0.93,
                    ],
                ],
                'training_samples' => 8000,
                'testing_samples' => 2000,
                'epochs' => 100,
            ]);

            // Update model
            $model->update([
                'status' => 'trained',
                'model_type' => ['random_forest', 'neural_network', 'svm'][$index % 3],
                'best_accuracy' => 0.9200 + ($index * 0.01),
                'trained_at' => now()->subHours(1),
                'is_active' => $index === 0, // First model is active
            ]);

            $this->command->info("✓ Created metrics for model: {$model->name}");
        }

        $this->command->info('✓ ML Metrics seeded successfully!');
    }
}
