<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MLModelMetric extends Model
{
    use HasFactory;

    protected $table = 'ml_model_metrics';

    protected $fillable = [
        'ml_model_id',
        'training_session_id',
        'accuracy',
        'precision',
        'recall',
        'f1_score',
        'confusion_matrix',
        'classification_report',
        'roc_curve_data',
        'feature_importance',
        'training_samples',
        'testing_samples',
        'epochs',
        'loss_history',
        'accuracy_history',
    ];

    protected $casts = [
        'accuracy' => 'decimal:4',
        'precision' => 'decimal:4',
        'recall' => 'decimal:4',
        'f1_score' => 'decimal:4',
        'confusion_matrix' => 'array',
        'classification_report' => 'array',
        'roc_curve_data' => 'array',
        'feature_importance' => 'array',
        'loss_history' => 'array',
        'accuracy_history' => 'array',
        'training_samples' => 'integer',
        'testing_samples' => 'integer',
        'epochs' => 'integer',
    ];

    /**
     * Get the ML model associated with these metrics
     */
    public function mlModel()
    {
        return $this->belongsTo(MLModel::class);
    }

    /**
     * Get the training session associated with these metrics
     */
    public function trainingSession()
    {
        return $this->belongsTo(MLTrainingSession::class, 'training_session_id');
    }

    /**
     * Get formatted accuracy percentage
     */
    public function getAccuracyPercentageAttribute(): string
    {
        return number_format($this->accuracy * 100, 2) . '%';
    }

    /**
     * Get formatted precision percentage
     */
    public function getPrecisionPercentageAttribute(): string
    {
        return number_format($this->precision * 100, 2) . '%';
    }

    /**
     * Get formatted recall percentage
     */
    public function getRecallPercentageAttribute(): string
    {
        return number_format($this->recall * 100, 2) . '%';
    }

    /**
     * Get formatted F1 score percentage
     */
    public function getF1ScorePercentageAttribute(): string
    {
        return number_format($this->f1_score * 100, 2) . '%';
    }
}
