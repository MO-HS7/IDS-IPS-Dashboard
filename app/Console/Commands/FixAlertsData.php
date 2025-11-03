<?php

namespace App\Console\Commands;

use App\Models\Alert;
use Illuminate\Console\Command;

class FixAlertsData extends Command
{
    protected $signature = 'alerts:fix-data';
    protected $description = 'Fix existing alerts severity data';

    public function handle()
    {
        $this->info('Fixing alerts severity data...');
        
        $alerts = Alert::all();
        $fixed = 0;
        
        foreach ($alerts as $alert) {
            $originalSeverity = $alert->getRawOriginal('severity');
            
            // إذا كانت القيمة فارغة أو null، اجعلها medium
            if (empty($originalSeverity)) {
                $alert->severity = 'medium';
                $alert->save();
                $fixed++;
            } else {
                // تأكد من أن القيمة صحيحة
                $validSeverities = ['low', 'medium', 'high', 'critical'];
                if (!in_array(strtolower($originalSeverity), $validSeverities)) {
                    $alert->severity = 'medium';
                    $alert->save();
                    $fixed++;
                }
            }
        }
        
        $this->info("Fixed {$fixed} alerts out of {$alerts->count()} total alerts.");
        
        // عرض عينة من البيانات
        $this->info('Current alerts data:');
        $sampleAlerts = Alert::select('id', 'attack_type', 'severity')->limit(5)->get();
        
        foreach ($sampleAlerts as $alert) {
            $this->line("Alert #{$alert->id}: {$alert->attack_type} - {$alert->severity}");
        }
        
        return 0;
    }
}
