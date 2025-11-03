<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MLTrainingSession extends Model
{
    use HasFactory;

    protected $table = 'ml_training_sessions';

    protected $fillable = [
        'ml_model_id',
        'user_id',
        'session_id',
        'status',
        'model_type',
        'hyperparameters',
        'dataset_path',
        'dataset_size',
        'progress',
        'status_message',
        'error_message',
        'accuracy',
        'precision',
        'recall',
        'f1_score',
        'started_at',
        'completed_at',
        'duration_seconds',
    ];

    protected $casts = [
        'hyperparameters' => 'array',
        'accuracy' => 'decimal:4',
        'precision' => 'decimal:4',
        'recall' => 'decimal:4',
        'f1_score' => 'decimal:4',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'progress' => 'integer',
        'dataset_size' => 'integer',
        'duration_seconds' => 'integer',
    ];

    /**
     * Get the ML model associated with this session
     */
    public function mlModel()
    {
        return $this->belongsTo(MLModel::class);
    }

    /**
     * Get the user who initiated this training session
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the metrics for this training session
     */
    public function metrics()
    {
        return $this->hasOne(MLModelMetric::class, 'training_session_id');
    }

    /**
     * Check if session is running
     */
    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    /**
     * Check if session is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if session has failed
     */
    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Mark session as started
     */
    public function markAsStarted(): void
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
            'progress' => 0,
        ]);
    }

    /**
     * Update progress
     */
    public function updateProgress(int $progress, string $message = null): void
    {
        $this->update([
            'progress' => min(100, max(0, $progress)),
            'status_message' => $message,
        ]);
    }

    /**
     * Mark session as completed
     */
    public function markAsCompleted(array $results): void
    {
        $duration = $this->started_at ? now()->diffInSeconds($this->started_at) : 0;
        
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'progress' => 100,
            'accuracy' => $results['accuracy'] ?? null,
            'precision' => $results['precision'] ?? null,
            'recall' => $results['recall'] ?? null,
            'f1_score' => $results['f1_score'] ?? null,
            'duration_seconds' => $duration,
            'status_message' => 'Training completed successfully',
        ]);
    }

    /**
     * Mark session as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
            'error_message' => $errorMessage,
            'status_message' => 'Training failed',
        ]);
    }
}
