<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Investigation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'assigned_to',
        'created_by',
        'started_at',
        'resolved_at',
        'timeline',
        'evidence',
        'resolution_notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'resolved_at' => 'datetime',
        'timeline' => 'array',
        'evidence' => 'array',
    ];

    public function alerts()
    {
        return $this->belongsToMany(Alert::class, 'alert_investigation');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function addTimelineEvent(string $event, string $description = null)
    {
        $timeline = $this->timeline ?? [];
        $timeline[] = [
            'event' => $event,
            'description' => $description,
            'user_id' => auth()->id(),
            'timestamp' => now()->toISOString(),
        ];
        $this->update(['timeline' => $timeline]);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }
}
