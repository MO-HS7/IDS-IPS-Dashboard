<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Alert;
use App\Models\NetworkLog;
use App\Models\MLModel;
use App\Models\User;
use Carbon\Carbon;

class DashboardDemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds for demo dashboard data.
     */
    public function run(): void
    {
        // Check if we have a user, if not create one
        $user = User::first();
        if (!$user) {
            $user = User::create([
                'name' => 'Demo User',
                'email' => 'demo@ids.local',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]);
        }

        // Check if we have an ML model, if not create one
        $mlModel = MLModel::first();
        if (!$mlModel) {
            $mlModel = MLModel::create([
                'name' => 'Random Forest Classifier',
                'version' => '1.0.0',
                'type' => 'classification',
                'accuracy' => 0.95,
                'status' => 'active',
                'trained_at' => now()->subDays(30),
            ]);
        }

        // Create network logs for the past 30 days (for analytics)
        $networkLogs = [];
        for ($i = 30; $i >= 0; $i--) {
            for ($j = 0; $j < rand(10, 25); $j++) {
                $dateTime = now()->subDays($i)->subMinutes(rand(0, 1440));
                $networkLogs[] = NetworkLog::create([
                    'user_id' => $user->id,
                    'file_name' => 'demo_capture_' . $i . '_' . $j . '.pcap',
                    'file_path' => 'network_logs/demo_' . $i . '_' . $j . '.pcap',
                    'upload_date' => $dateTime,
                    'status' => 'processed',
                    'source_ip' => $this->generateRandomIP(),
                    'destination_ip' => $this->generateRandomIP(),
                    'source_port' => rand(1024, 65535),
                    'destination_port' => rand(80, 443) == 80 ? 80 : 443,
                    'protocol' => collect(['TCP', 'UDP', 'ICMP'])->random(),
                    'packet_size' => rand(64, 1500),
                    'file_size' => rand(1024, 1024*1024*10), // 1KB to 10MB for analytics
                    'timestamp' => $dateTime,
                    'created_at' => $dateTime,
                    'processed' => true,
                ]);
            }
        }

        // Create alerts with different severities and attack types
        $attackTypes = [
            'DDoS Attack' => ['critical', 'high'],
            'Port Scan' => ['medium', 'low'],
            'SQL Injection' => ['critical', 'high'],
            'XSS Attack' => ['medium', 'high'],
            'Brute Force' => ['high', 'medium'],
            'Man in the Middle' => ['critical', 'high'],
            'DNS Spoofing' => ['high', 'medium'],
            'Malware Detection' => ['critical', 'high'],
        ];

        foreach ($attackTypes as $attackType => $severities) {
            for ($i = 30; $i >= 0; $i--) {
                // Create 1-5 alerts per day for each attack type (more for analytics)
                $alertsCount = $i < 7 ? rand(2, 5) : rand(1, 3);
                for ($j = 0; $j < $alertsCount; $j++) {
                    $networkLog = $networkLogs[array_rand($networkLogs)];
                    
                    Alert::create([
                        'network_log_id' => $networkLog->id,
                        'ml_model_id' => $mlModel->id,
                        'attack_type' => $attackType,
                        'severity' => $severities[array_rand($severities)],
                        'source_ip' => $networkLog->source_ip,
                        'destination_ip' => $networkLog->destination_ip,
                        'confidence_score' => rand(70, 99) / 100,
                        'status' => collect(['new', 'investigating', 'resolved'])->random(),
                        'detected_at' => now()->subDays($i)->subMinutes(rand(0, 1440)),
                        'description' => "Detected {$attackType} from {$networkLog->source_ip} targeting {$networkLog->destination_ip}",
                    ]);
                }
            }
        }

        $this->command->info('✅ Dashboard demo data created successfully!');
        $this->command->info('📊 Created ' . count($networkLogs) . ' network logs');
        $this->command->info('🚨 Created ' . Alert::count() . ' alerts');
    }

    /**
     * Generate a random IP address
     */
    private function generateRandomIP(): string
    {
        return rand(1, 255) . '.' . rand(0, 255) . '.' . rand(0, 255) . '.' . rand(1, 255);
    }
}
