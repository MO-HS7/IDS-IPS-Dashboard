<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LiveMonitoringSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'interface',
        'status',
        'started_at',
        'stopped_at',
        'packets_captured',
        'threats_detected',
        'statistics',
        'error_message',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'stopped_at' => 'datetime',
        'statistics' => 'array',
        'packets_captured' => 'integer',
        'threats_detected' => 'integer',
    ];

    /**
     * Boot function to generate session_id automatically
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($session) {
            if (empty($session->session_id)) {
                $session->session_id = Str::uuid()->toString();
            }
        });
    }

    /**
     * Get the user that owns the monitoring session
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all packets captured in this session
     */
    public function packets()
    {
        return $this->hasMany(LiveCapturedPacket::class, 'session_id');
    }

    /**
     * Get only threat packets
     */
    public function threatPackets()
    {
        return $this->hasMany(LiveCapturedPacket::class, 'session_id')
            ->where('is_threat', true);
    }

    /**
     * Check if session is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Stop the monitoring session
     */
    public function stop(): void
    {
        $this->update([
            'status' => 'stopped',
            'stopped_at' => now(),
        ]);
    }

    /**
     * Mark session as error
     */
    public function markAsError(string $message): void
    {
        $this->update([
            'status' => 'error',
            'error_message' => $message,
            'stopped_at' => now(),
        ]);
    }

    /**
     * Increment packet count
     */
    public function incrementPacketCount(): void
    {
        $this->increment('packets_captured');
    }

    /**
     * Increment threat count
     */
    public function incrementThreatCount(): void
    {
        $this->increment('threats_detected');
    }
}
