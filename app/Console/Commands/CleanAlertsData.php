<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanAlertsData extends Command
{
    protected $signature = 'alerts:clean';
    protected $description = 'Clean and fix alerts severity data';

    public function handle()
    {
        $this->info('Checking current alerts data...');
        
        // فحص البيانات الحالية
        $alerts = DB::table('alerts')->select('id', 'attack_type', 'severity')->get();
        
        $this->info('Current data:');
        foreach ($alerts as $alert) {
            $this->line("ID: {$alert->id}, Type: {$alert->attack_type}, Severity: '{$alert->severity}'");
        }
        
        // تنظيف البيانات
        $this->info('Cleaning data...');
        
        // إصلاح القيم NULL أو الفارغة
        $nullFixed = DB::table('alerts')
            ->whereNull('severity')
            ->orWhere('severity', '')
            ->update(['severity' => 'medium']);
        
        $this->info("Fixed {$nullFixed} NULL/empty severity values");
        
        // إصلاح القيم غير الصحيحة
        $invalidFixed = DB::table('alerts')
            ->whereNotIn('severity', ['low', 'medium', 'high', 'critical'])
            ->update(['severity' => 'medium']);
        
        $this->info("Fixed {$invalidFixed} invalid severity values");
        
        // تحويل إلى lowercase
        DB::statement("UPDATE alerts SET severity = LOWER(severity)");
        
        $this->info('Converted all severity values to lowercase');
        
        // عرض البيانات بعد التنظيف
        $cleanedAlerts = DB::table('alerts')->select('id', 'attack_type', 'severity')->get();
        
        $this->info('Data after cleaning:');
        foreach ($cleanedAlerts as $alert) {
            $this->line("ID: {$alert->id}, Type: {$alert->attack_type}, Severity: '{$alert->severity}'");
        }
        
        // إحصائيات
        $severityCounts = DB::table('alerts')
            ->select('severity', DB::raw('count(*) as count'))
            ->groupBy('severity')
            ->get();
            
        $this->info('Severity distribution:');
        foreach ($severityCounts as $severity) {
            $this->line("{$severity->severity}: {$severity->count}");
        }
        
        return 0;
    }
}
