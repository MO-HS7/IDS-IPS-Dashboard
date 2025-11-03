<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NetworkLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'packet_count',
        'capture_start_time',
        'capture_end_time',
        'capture_duration',
        'file_metadata',
        'processing_error',
        'processing_progress',
        'upload_date',
        'status'
    ];

    protected $casts = [
        'upload_date' => 'datetime',
        'capture_start_time' => 'datetime',
        'capture_end_time' => 'datetime',
        'file_metadata' => 'array',
        'file_size' => 'integer',
        'packet_count' => 'integer',
        'capture_duration' => 'integer',
        'processing_progress' => 'integer'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function alerts()
    {
        return $this->hasMany(Alert::class);
    }

    /**
     * Check if file is PCAP format
     */
    public function isPcap(): bool
    {
        return in_array($this->file_type, ['pcap', 'pcapng']);
    }

    /**
     * Check if file is CSV format
     */
    public function isCsv(): bool
    {
        return $this->file_type === 'csv';
    }

    /**
     * Get formatted file size
     */
    public function getFormattedFileSizeAttribute(): string
    {
        if (!$this->file_size) {
            return 'Unknown';
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Update processing progress
     */
    public function updateProgress(int $progress, string $status = null): void
    {
        $this->update([
            'processing_progress' => min(100, max(0, $progress)),
            'status' => $status ?? $this->status
        ]);
    }

    /**
     * Mark as processing failed
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'processing_error' => $error,
            'processing_progress' => 0
        ]);
    }

    /**
     * Mark as processing completed
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'processed',
            'processing_progress' => 100
        ]);
    }
}
