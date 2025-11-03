<?php

namespace Database\Seeders;

use App\Models\Alert;
use App\Models\NetworkLog;
use App\Models\MLModel;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds for demo/testing purposes.
     */
    public function run(): void
    {
        $this->command->info('Seeding demo data...');

        // Check if required tables exist
        $requiredTables = ['users', 'ml_models', 'network_logs', 'alerts'];
        foreach ($requiredTables as $table) {
            if (!\Schema::hasTable($table)) {
                $this->command->error("Required table '{$table}' does not exist. Please run migrations first.");
                return;
            }
        }

        // Create a demo user if no users exist
        $user = User::firstOrCreate(
            ['email' => 'admin@ids.local'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('User created: ' . $user->email);

        // Create ML Models
        $models = [
            [
                'name' => 'Random Forest Classifier',
                'model_type' => 'RandomForest',
                'version' => '1.0.0',
                'status' => 'trained',
                'accuracy' => 0.95,
                'precision' => 0.93,
                'recall' => 0.94,
                'f1_score' => 0.935,
                'description' => 'Primary model for intrusion detection using Random Forest algorithm',
            ],
            [
                'name' => 'Neural Network IDS',
                'model_type' => 'NeuralNetwork',
                'version' => '2.1.0',
                'status' => 'trained',
                'accuracy' => 0.97,
                'precision' => 0.96,
                'recall' => 0.95,
                'f1_score' => 0.955,
                'description' => 'Deep learning model for advanced threat detection',
            ],
            [
                'name' => 'SVM Classifier',
                'model_type' => 'SVM',
                'version' => '1.2.0',
                'status' => 'trained',
                'accuracy' => 0.92,
                'precision' => 0.91,
                'recall' => 0.90,
                'f1_score' => 0.905,
                'description' => 'Support Vector Machine for binary classification',
            ],
        ];

        foreach ($models as $modelData) {
            MLModel::firstOrCreate(
                ['name' => $modelData['name']],
                $modelData
            );
        }

        $this->command->info('ML Models created: ' . count($models));

        // Get the first ML model for relationships
        $mlModel = MLModel::first();

        // Create Network Logs (past 30 days)
        $networkLogs = [];
        for ($i = 0; $i < 50; $i++) {
            $networkLogs[] = NetworkLog::create([
                'user_id' => $user->id,
                'file_name' => "capture_{$i}_" . Carbon::now()->subDays(rand(0, 30))->format('Y-m-d') . ".pcap",
                'file_path' => "storage/pcap/capture_{$i}.pcap",
                'file_size' => rand(1024, 10485760), // 1KB to 10MB
                'status' => collect(['processed', 'processed', 'processed', 'pending'])->random(),
                'upload_date' => Carbon::now()->subDays(rand(0, 30)),
                'analysis_results' => json_encode([
                    'total_packets' => rand(100, 5000),
                    'protocols' => ['TCP', 'UDP', 'ICMP', 'HTTP', 'HTTPS'],
                    'suspicious_patterns' => rand(0, 10),
                ]),
            ]);
        }

        $this->command->info('Network Logs created: ' . count($networkLogs));

        // Create Alerts with various severities and attack types
        $attackTypes = [
            'Port Scan' => ['low', 'medium'],
            'DDoS Attack' => ['high', 'critical'],
            'SQL Injection' => ['high', 'critical'],
            'Brute Force' => ['medium', 'high'],
            'Malware Detection' => ['critical'],
            'Unauthorized Access' => ['high', 'critical'],
            'Data Exfiltration' => ['critical'],
            'XSS Attack' => ['medium', 'high'],
            'CSRF Attack' => ['medium'],
            'Man-in-the-Middle' => ['high', 'critical'],
        ];

        $sourceIPs = [
            '192.168.1.100', '10.0.0.55', '172.16.0.25', '203.0.113.45',
            '198.51.100.78', '192.0.2.100', '10.10.10.10', '172.31.255.255'
        ];

        $destIPs = [
            '192.168.1.1', '10.0.0.1', '172.16.0.1', '8.8.8.8',
            '1.1.1.1', '192.168.1.10', '10.0.0.10', '172.16.0.10'
        ];

        $alerts = [];
        foreach ($attackTypes as $attackType => $severities) {
            for ($i = 0; $i < rand(3, 8); $i++) {
                $severity = $severities[array_rand($severities)];
                $daysAgo = rand(0, 7);
                
                $alerts[] = Alert::create([
                    'network_log_id' => $networkLogs[array_rand($networkLogs)]->id,
                    'ml_model_id' => $mlModel->id,
                    'attack_type' => $attackType,
                    'severity' => $severity,
                    'confidence_score' => rand(70, 99) / 100,
                    'description' => "Detected {$attackType} with {$severity} severity. Immediate attention may be required.",
                    'source_ip' => $sourceIPs[array_rand($sourceIPs)],
                    'destination_ip' => $destIPs[array_rand($destIPs)],
                    'source_port' => rand(1024, 65535),
                    'destination_port' => collect([80, 443, 22, 3306, 8080, 21])->random(),
                    'protocol' => collect(['TCP', 'UDP', 'ICMP', 'HTTP'])->random(),
                    'detected_at' => Carbon::now()->subDays($daysAgo)->subHours(rand(0, 23)),
                    'status' => collect(['new', 'investigating', 'resolved'])->random(),
                    'mitigated' => rand(0, 1),
                    'false_positive' => rand(0, 10) == 0 ? 1 : 0, // 10% chance
                ]);
            }
        }

        $this->command->info('Alerts created: ' . count($alerts));

        // Summary
        $this->command->newLine();
        $this->command->info('=== Demo Data Seeding Complete ===');
        $this->command->table(
            ['Resource', 'Count'],
            [
                ['Users', User::count()],
                ['ML Models', MLModel::count()],
                ['Network Logs', NetworkLog::count()],
                ['Alerts', Alert::count()],
            ]
        );
        $this->command->info('Demo user credentials: admin@ids.local / password');
    }
}
