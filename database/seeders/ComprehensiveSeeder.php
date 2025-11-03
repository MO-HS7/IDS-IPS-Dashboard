<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ComprehensiveSeeder extends Seeder
{
    /**
     * Run comprehensive database seeding for all tables
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting Comprehensive Database Seeding...');
        $this->command->newLine();

        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        try {
            // 1. Users (Foundation)
            $this->command->info('👥 Seeding Users...');
            $this->call(TestDataSeeder::class);
            $this->command->info('✓ Users seeded successfully');
            $this->command->newLine();

            // 2. ML Models & Metrics
            $this->command->info('🤖 Seeding ML Models & Metrics...');
            $this->call(MLMetricsSeeder::class);
            $this->command->info('✓ ML Models & Metrics seeded successfully');
            $this->command->newLine();

            // 3. Notifications
            $this->command->info('🔔 Seeding Notifications...');
            $this->call(NotificationsSeeder::class);
            $this->command->info('✓ Notifications seeded successfully');
            $this->command->newLine();

            // 4. Summary
            $this->command->newLine();
            $this->command->info('═══════════════════════════════════════════');
            $this->command->info('✅ COMPREHENSIVE SEEDING COMPLETED!');
            $this->command->info('═══════════════════════════════════════════');
            $this->command->newLine();
            
            $this->displaySummary();

        } catch (\Exception $e) {
            $this->command->error('❌ Seeding failed: ' . $e->getMessage());
            $this->command->error($e->getTraceAsString());
        } finally {
            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    private function displaySummary(): void
    {
        $summary = [
            ['Table', 'Count', 'Status'],
            ['users', DB::table('users')->count(), '✓'],
            ['ml_models', DB::table('ml_models')->count(), '✓'],
            ['ml_training_sessions', DB::table('ml_training_sessions')->count(), '✓'],
            ['ml_model_metrics', DB::table('ml_model_metrics')->count(), '✓'],
            ['notifications', DB::table('notifications')->count(), '✓'],
            ['alerts', DB::table('alerts')->count(), '✓'],
            ['network_logs', DB::table('network_logs')->count(), '✓'],
        ];

        $this->command->table($summary[0], array_slice($summary, 1));
        
        $this->command->newLine();
        $this->command->info('📊 Total Records: ' . array_sum(array_column(array_slice($summary, 1), 1)));
        $this->command->newLine();
        
        $this->command->info('🎯 Quick Start:');
        $this->command->info('   Email: admin@example.com');
        $this->command->info('   Password: password');
        $this->command->newLine();
        
        $this->command->info('🔗 Available Routes:');
        $this->command->info('   → http://localhost:8000/dashboard');
        $this->command->info('   → http://localhost:8000/ml-models');
        $this->command->info('   → http://localhost:8000/alerts');
        $this->command->info('   → http://localhost:8000/notifications');
        $this->command->info('   → http://localhost:8000/analytics');
        $this->command->newLine();
    }
}
