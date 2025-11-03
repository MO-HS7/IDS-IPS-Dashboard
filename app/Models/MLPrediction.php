<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MLPrediction extends Model
{
    use HasFactory;

    protected $table = 'ml_predictions';

    protected $fillable = [
        'ml_model_id',
        'network_log_id',
        'prediction',
        'confidence',
        'is_attack',
        'severity',
        'input_features',
        'probability_distribution',
        'predicted_at',
    ];

    protected $casts = [
        'confidence' => 'decimal:4',
        'is_attack' => 'boolean',
        'input_features' => 'array',
        'probability_distribution' => 'array',
        'predicted_at' => 'datetime',
    ];

    /**
     * Get the ML model that made this prediction
     */
    public function mlModel()
    {
        return $this->belongsTo(MLModel::class);
    }

    /**
     * Get the network log associated with this prediction
     */
    public function networkLog()
    {
        return $this->belongsTo(NetworkLog::class);
    }

    /**
     * Get formatted confidence percentage
     */
    public function getConfidencePercentageAttribute(): string
    {
        return number_format($this->confidence * 100, 2) . '%';
    }

    /**
     * Get severity badge color
     */
    public function getSeverityColorAttribute(): string
    {
        return match($this->severity) {
            'low' => 'green',
            'medium' => 'yellow',
            'high' => 'orange',
            'critical' => 'red',
            default => 'gray',
        };
    }

    /**
     * Scope to filter attacks only
     */
    public function scopeAttacks($query)
    {
        return $query->where('is_attack', true);
    }

    /**
     * Scope to filter normal traffic only
     */
    public function scopeNormal($query)
    {
        return $query->where('is_attack', false);
    }

    /**
     * Scope to filter by severity
     */
    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }
}
