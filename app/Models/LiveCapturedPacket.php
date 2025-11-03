<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LiveCapturedPacket extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'captured_at',
        'source_ip',
        'destination_ip',
        'source_port',
        'destination_port',
        'protocol',
        'packet_length',
        'payload_preview',
        'flags',
        'threat_score',
        'is_threat',
        'threat_type',
        'raw_data',
    ];

    protected $casts = [
        'captured_at' => 'datetime',
        'source_port' => 'integer',
        'destination_port' => 'integer',
        'packet_length' => 'integer',
        'threat_score' => 'decimal:2',
        'is_threat' => 'boolean',
        'raw_data' => 'array',
    ];

    /**
     * Get the session that owns the packet
     */
    public function session()
    {
        return $this->belongsTo(LiveMonitoringSession::class, 'session_id');
    }

    /**
     * Scope to filter threat packets
     */
    public function scopeThreats($query)
    {
        return $query->where('is_threat', true);
    }

    /**
     * Scope to filter by protocol
     */
    public function scopeByProtocol($query, string $protocol)
    {
        return $query->where('protocol', $protocol);
    }

    /**
     * Scope to filter by IP
     */
    public function scopeBySourceIp($query, string $ip)
    {
        return $query->where('source_ip', $ip);
    }

    /**
     * Get formatted packet info
     */
    public function getFormattedInfo(): array
    {
        return [
            'id' => $this->id,
            'time' => $this->captured_at->format('H:i:s.u'),
            'source' => $this->source_ip . ($this->source_port ? ':' . $this->source_port : ''),
            'destination' => $this->destination_ip . ($this->destination_port ? ':' . $this->destination_port : ''),
            'protocol' => $this->protocol,
            'length' => $this->packet_length,
            'threat_score' => $this->threat_score,
            'is_threat' => $this->is_threat,
            'threat_type' => $this->threat_type,
            'flags' => $this->flags,
        ];
    }
}
