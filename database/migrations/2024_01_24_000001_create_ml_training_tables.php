<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Training Sessions Table
        if (!Schema::hasTable('ml_training_sessions')) {
            Schema::create('ml_training_sessions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ml_model_id');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('session_id')->unique();
                $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending');

                // Training Configuration
                $table->string('model_type'); // random_forest, neural_network, svm, etc.
                $table->json('hyperparameters')->nullable();
                $table->string('dataset_path')->nullable();
                $table->integer('dataset_size')->nullable();

                // Training Progress
                $table->integer('progress')->default(0); // 0-100
                $table->text('status_message')->nullable();
                $table->text('error_message')->nullable();

                // Training Results
                $table->decimal('accuracy', 5, 4)->nullable();
                $table->decimal('precision', 5, 4)->nullable();
                $table->decimal('recall', 5, 4)->nullable();
                $table->decimal('f1_score', 5, 4)->nullable();

                // Timing
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->integer('duration_seconds')->nullable();

                $table->timestamps();

                $table->index('session_id');
                $table->index('status');
                $table->index('ml_model_id');
            });

            // Add foreign key after table creation if ml_models exists
            if (Schema::hasTable('ml_models')) {
                Schema::table('ml_training_sessions', function (Blueprint $table) {
                    $table->foreign('ml_model_id')->references('id')->on('ml_models')->onDelete('cascade');
                });
            }
        }

        // Model Performance Metrics Table
        if (!Schema::hasTable('ml_model_metrics')) {
            Schema::create('ml_model_metrics', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ml_model_id');
                $table->unsignedBigInteger('training_session_id')->nullable();

                // Performance Metrics
                $table->decimal('accuracy', 5, 4);
                $table->decimal('precision', 5, 4)->nullable();
                $table->decimal('recall', 5, 4)->nullable();
                $table->decimal('f1_score', 5, 4)->nullable();

                // Additional Metrics
                $table->json('confusion_matrix')->nullable();
                $table->json('classification_report')->nullable();
                $table->json('roc_curve_data')->nullable();
                $table->json('feature_importance')->nullable();

                // Training Details
                $table->integer('training_samples')->nullable();
                $table->integer('testing_samples')->nullable();
                $table->integer('epochs')->nullable();
                $table->json('loss_history')->nullable();
                $table->json('accuracy_history')->nullable();

                $table->timestamps();

                $table->index('ml_model_id');
            });

            // Add foreign keys after table creation if referenced tables exist
            if (Schema::hasTable('ml_models')) {
                Schema::table('ml_model_metrics', function (Blueprint $table) {
                    $table->foreign('ml_model_id')->references('id')->on('ml_models')->onDelete('cascade');
                });
            }
            if (Schema::hasTable('ml_training_sessions')) {
                Schema::table('ml_model_metrics', function (Blueprint $table) {
                    $table->foreign('training_session_id')->references('id')->on('ml_training_sessions')->onDelete('set null');
                });
            }
        }

        // Model Predictions Table
        if (!Schema::hasTable('ml_predictions')) {
            Schema::create('ml_predictions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ml_model_id');
                $table->unsignedBigInteger('network_log_id')->nullable();

                // Prediction Details
                $table->string('prediction'); // normal, dos, probe, etc.
                $table->decimal('confidence', 5, 4);
                $table->boolean('is_attack')->default(false);
                $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');

                // Input Features
                $table->json('input_features')->nullable();
                $table->json('probability_distribution')->nullable();

                // Timing
                $table->timestamp('predicted_at');

                $table->timestamps();

                $table->index('ml_model_id');
                $table->index('prediction');
                $table->index('is_attack');
                $table->index('predicted_at');
            });

            // Add foreign keys after table creation if referenced tables exist
            if (Schema::hasTable('ml_models')) {
                Schema::table('ml_predictions', function (Blueprint $table) {
                    $table->foreign('ml_model_id')->references('id')->on('ml_models')->onDelete('cascade');
                });
            }
            if (Schema::hasTable('network_logs')) {
                Schema::table('ml_predictions', function (Blueprint $table) {
                    $table->foreign('network_log_id')->references('id')->on('network_logs')->onDelete('cascade');
                });
            }
        }

        // Add additional columns to ml_models table
        if (Schema::hasTable('ml_models')) {
            Schema::table('ml_models', function (Blueprint $table) {
                if (!Schema::hasColumn('ml_models', 'model_type')) {
                    $table->string('model_type')->nullable()->after('description'); // random_forest, neural_network, etc.
                }
                if (!Schema::hasColumn('ml_models', 'version')) {
                    $table->string('version')->default('1.0')->after('model_type');
                }
                if (!Schema::hasColumn('ml_models', 'status')) {
                    $table->enum('status', ['untrained', 'training', 'trained', 'failed'])->default('untrained')->after('version');
                }
                if (!Schema::hasColumn('ml_models', 'hyperparameters')) {
                    $table->json('hyperparameters')->nullable()->after('status');
                }
                if (!Schema::hasColumn('ml_models', 'training_config')) {
                    $table->json('training_config')->nullable()->after('hyperparameters');
                }
                if (!Schema::hasColumn('ml_models', 'best_accuracy')) {
                    $table->decimal('best_accuracy', 5, 4)->nullable()->after('training_config');
                }
                if (!Schema::hasColumn('ml_models', 'total_predictions')) {
                    $table->integer('total_predictions')->default(0)->after('best_accuracy');
                }
                if (!Schema::hasColumn('ml_models', 'is_active')) {
                    $table->boolean('is_active')->default(false)->after('total_predictions');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ml_models', function (Blueprint $table) {
            $table->dropColumn([
                'model_type',
                'version',
                'status',
                'hyperparameters',
                'training_config',
                'best_accuracy',
                'total_predictions',
                'is_active'
            ]);
        });
        
        Schema::dropIfExists('ml_predictions');
        Schema::dropIfExists('ml_model_metrics');
        Schema::dropIfExists('ml_training_sessions');
    }
};
