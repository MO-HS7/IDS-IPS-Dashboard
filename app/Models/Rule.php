<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'signature',
        'category',
        'severity',
        'description',
        'enabled',
        'sid',
        'rev',
        'protocol',
        'source_ip',
        'source_port',
        'destination_ip',
        'destination_port',
        'action',
        'metadata',
        'alert_count',
        'last_triggered_at',
        'created_by',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'metadata' => 'array',
        'last_triggered_at' => 'datetime',
        'alert_count' => 'integer',
    ];

    /**
     * Get the user who created this rule.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to get only enabled rules.
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * Scope to filter by category.
     */
    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to filter by severity.
     */
    public function scopeSeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Parse a Snort rule signature and extract components.
     */
    public static function parseSignature(string $signature): array
    {
        $parsed = [
            'action' => null,
            'protocol' => null,
            'source_ip' => null,
            'source_port' => null,
            'destination_ip' => null,
            'destination_port' => null,
            'sid' => null,
            'rev' => 1,
            'msg' => null,
        ];

        // Example Snort rule format:
        // alert tcp any any -> any 80 (msg:"HTTP GET Request"; sid:1000001; rev:1;)
        
        if (preg_match('/^(\w+)\s+(\w+)\s+([^\s]+)\s+([^\s]+)\s+->\s+([^\s]+)\s+([^\s]+)\s+\((.*)\)/', $signature, $matches)) {
            $parsed['action'] = $matches[1] ?? 'alert';
            $parsed['protocol'] = $matches[2] ?? null;
            $parsed['source_ip'] = $matches[3] ?? null;
            $parsed['source_port'] = $matches[4] ?? null;
            $parsed['destination_ip'] = $matches[5] ?? null;
            $parsed['destination_port'] = $matches[6] ?? null;
            
            $options = $matches[7] ?? '';
            
            // Extract sid
            if (preg_match('/sid:\s*(\d+)/', $options, $sidMatch)) {
                $parsed['sid'] = (int)$sidMatch[1];
            }
            
            // Extract rev
            if (preg_match('/rev:\s*(\d+)/', $options, $revMatch)) {
                $parsed['rev'] = (int)$revMatch[1];
            }
            
            // Extract msg
            if (preg_match('/msg:\s*"([^"]+)"/', $options, $msgMatch)) {
                $parsed['msg'] = $msgMatch[1];
            }
        }

        return $parsed;
    }

    /**
     * Generate Snort rule signature from rule data.
     */
    public function generateSignature(): string
    {
        $options = [];
        
        if ($this->description) {
            $options[] = 'msg:"' . addslashes($this->description) . '"';
        }
        
        $options[] = "sid:{$this->sid}";
        $options[] = "rev:{$this->rev}";
        
        if ($this->metadata) {
            foreach ($this->metadata as $key => $value) {
                $options[] = "{$key}:\"{$value}\"";
            }
        }

        $optionsStr = implode('; ', $options) . ';';

        return sprintf(
            '%s %s %s %s -> %s %s (%s)',
            $this->action,
            $this->protocol ?? 'ip',
            $this->source_ip ?? 'any',
            $this->source_port ?? 'any',
            $this->destination_ip ?? 'any',
            $this->destination_port ?? 'any',
            $optionsStr
        );
    }

    /**
     * Increment the alert count for this rule.
     */
    public function incrementAlertCount()
    {
        $this->increment('alert_count');
        $this->update(['last_triggered_at' => now()]);
    }
}
