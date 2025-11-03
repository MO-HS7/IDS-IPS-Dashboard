<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MLModel extends Model
{
    use HasFactory;

    protected $table = 'ml_models';

    protected $fillable = [
        'name',
        'description',
        'file_path',
        'model_type',
        'version',
        'status',
        'hyperparameters',
        'training_config',
        'best_accuracy',
        'total_predictions',
        'is_active',
        'trained_at',
    ];

    protected $casts = [
        'trained_at' => 'datetime',
        'hyperparameters' => 'array',
        'training_config' => 'array',
        'best_accuracy' => 'decimal:4',
        'total_predictions' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the alerts for the ML model.
     */
    public function alerts()
    {
        return $this->hasMany(Alert::class);
    }

    /**
     * Get training sessions for this model
     */
    public function trainingSessions()
    {
        return $this->hasMany(MLTrainingSession::class, 'ml_model_id');
    }

    /**
     * Get the latest training session
     */
    public function latestTrainingSession()
    {
        return $this->hasOne(MLTrainingSession::class, 'ml_model_id')->latestOfMany();
    }

    /**
     * Get metrics for this model
     */
    public function metrics()
    {
        return $this->hasMany(MLModelMetric::class, 'ml_model_id');
    }

    /**
     * Get the latest metrics
     */
    public function latestMetric()
    {
        return $this->hasOne(MLModelMetric::class, 'ml_model_id')->latestOfMany();
    }

    /**
     * Get predictions made by this model
     */
    public function predictions()
    {
        return $this->hasMany(MLPrediction::class, 'ml_model_id');
    }

    /**
     * Check if model is trained
     */
    public function isTrained(): bool
    {
        return $this->status === 'trained';
    }

    /**
     * Check if model is training
     */
    public function isTraining(): bool
    {
        return $this->status === 'training';
    }

    /**
     * Mark model as active
     */
    public function activate(): void
    {
        // Deactivate all other models of the same type
        self::where('model_type', $this->model_type)
            ->where('id', '!=', $this->id)
            ->update(['is_active' => false]);

        $this->update(['is_active' => true]);
    }

    /**
     * Increment prediction count
     */
    public function incrementPredictions(int $count = 1): void
    {
        $this->increment('total_predictions', $count);
    }

    /**
     * Get formatted accuracy percentage
     */
    public function getBestAccuracyPercentageAttribute(): ?string
    {
        return $this->best_accuracy 
            ? number_format($this->best_accuracy * 100, 2) . '%' 
            : null;
    }

    /**
     * Scope to get only trained models
     */
    public function scopeTrained($query)
    {
        return $query->where('status', 'trained');
    }

    /**
     * Scope to get only active models
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
