<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Alert;
use App\Models\NetworkLog;
use App\Models\MLModel;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🌱 Starting to seed test data...');

        // Create test users
        $admin = User::firstOrCreate(
            ['email' => 'admin@aiids.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'role' => 'Admin',
                'email_verified_at' => now(),
            ]
        );

        $analyst = User::firstOrCreate(
            ['email' => 'analyst@aiids.com'],
            [
                'name' => 'Security Analyst',
                'password' => Hash::make('password'),
                'role' => 'Analyst',
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('✓ Users created');

        // Create Network Logs (only columns that exist: user_id, file_name, file_path, upload_date, status, analysis_result)
        $networkLogsData = [
            ['file_name' => 'firewall_logs_2024_03_15.log', 'file_path' => '/logs/firewall_logs_2024_03_15.log', 'upload_date' => Carbon::now()->subDays(2), 'status' => 'processed', 'analysis_result' => 'Firewall activity logs - no threats detected'],
            ['file_name' => 'network_traffic_morning.pcap', 'file_path' => '/logs/network_traffic_morning.pcap', 'upload_date' => Carbon::now()->subDays(1), 'status' => 'processed', 'analysis_result' => 'Network packet capture analyzed - 5 threats found'],
            ['file_name' => 'ids_alerts_march.csv', 'file_path' => '/logs/ids_alerts_march.csv', 'upload_date' => Carbon::now()->subDays(5), 'status' => 'processed', 'analysis_result' => 'IDS alert logs analyzed successfully'],
            ['file_name' => 'apache_access.log', 'file_path' => '/logs/apache_access.log', 'upload_date' => Carbon::now()->subHours(12), 'status' => 'processing', 'analysis_result' => null],
            ['file_name' => 'vpn_connections.log', 'file_path' => '/logs/vpn_connections.log', 'upload_date' => Carbon::now()->subDays(3), 'status' => 'processed', 'analysis_result' => 'VPN connection logs - all connections secure'],
        ];

        $networkLogs = [];
        foreach ($networkLogsData as $logData) {
            $log = NetworkLog::firstOrCreate(
                ['file_name' => $logData['file_name']],
                array_merge($logData, ['user_id' => $analyst->id])
            );
            $networkLogs[] = $log;
        }

        $this->command->info('✓ Network logs created (' . count($networkLogs) . ')');

        // Create ML Models (only columns: name, description, file_path, trained_at)
        $mlModelsData = [
            ['name' => 'Random Forest Classifier', 'description' => 'Random Forest model for DDoS detection - 94.5% accuracy', 'file_path' => '/models/random_forest_ddos.pkl', 'trained_at' => Carbon::now()->subWeeks(2)],
            ['name' => 'Neural Network - Malware', 'description' => 'Deep learning model for malware detection - 96.8% accuracy', 'file_path' => '/models/neural_network_malware.h5', 'trained_at' => Carbon::now()->subWeek()],
            ['name' => 'SVM Intrusion Detector', 'description' => 'Support Vector Machine for network intrusion - 91.2% accuracy', 'file_path' => '/models/svm_intrusion.pkl', 'trained_at' => Carbon::now()->subMonth()],
            ['name' => 'Decision Tree - Port Scan', 'description' => 'Decision tree classifier for port scanning - 88.7% accuracy', 'file_path' => '/models/decision_tree_portscan.pkl', 'trained_at' => Carbon::now()->subDays(3)],
            ['name' => 'Logistic Regression - Phishing', 'description' => 'Logistic regression for phishing detection - 93.4% accuracy', 'file_path' => '/models/logistic_phishing.pkl', 'trained_at' => Carbon::now()->subWeeks(3)],
        ];

        $mlModels = [];
        foreach ($mlModelsData as $modelData) {
            $model = MLModel::firstOrCreate(
                ['name' => $modelData['name']],
                $modelData
            );
            $mlModels[] = $model;
        }

        $this->command->info('✓ ML Models created (' . count($mlModels) . ')');

        // Create Alerts (columns: network_log_id, ml_model_id, attack_type, severity, source_ip, destination_ip, confidence_score, status, detected_at, description)
        $alertsData = [
            ['attack_type' => 'Man-in-the-Middle Attack', 'severity' => 'critical', 'description' => 'Suspicious ARP spoofing detected. Potential MITM attack in progress.', 'source_ip' => '192.168.1.105', 'destination_ip' => '192.168.1.1', 'detected_at' => Carbon::now()->subHours(2), 'status' => 'new', 'confidence_score' => 0.95],
            ['attack_type' => 'DDoS Attack', 'severity' => 'high', 'description' => 'Large volume of requests from multiple sources detected. Possible DDoS attack.', 'source_ip' => '203.0.113.45', 'destination_ip' => '192.168.1.10', 'detected_at' => Carbon::now()->subHours(5), 'status' => 'investigating', 'confidence_score' => 0.92],
            ['attack_type' => 'SQL Injection Attempt', 'severity' => 'high', 'description' => 'SQL injection patterns detected in web traffic. Attacker attempting database breach.', 'source_ip' => '198.51.100.23', 'destination_ip' => '192.168.1.20', 'detected_at' => Carbon::now()->subHours(8), 'status' => 'resolved', 'confidence_score' => 0.89],
            ['attack_type' => 'Brute Force Attack', 'severity' => 'medium', 'description' => 'Multiple failed login attempts detected from single IP address.', 'source_ip' => '203.0.113.78', 'destination_ip' => '192.168.1.15', 'detected_at' => Carbon::now()->subHours(12), 'status' => 'investigating', 'confidence_score' => 0.87],
            ['attack_type' => 'Port Scanning', 'severity' => 'medium', 'description' => 'Sequential port scanning activity detected. Attacker probing for vulnerabilities.', 'source_ip' => '198.51.100.89', 'destination_ip' => '192.168.1.5', 'detected_at' => Carbon::now()->subDay(), 'status' => 'resolved', 'confidence_score' => 0.84],
            ['attack_type' => 'Malware Detection', 'severity' => 'critical', 'description' => 'Malicious payload detected in network traffic. Possible trojan or ransomware.', 'source_ip' => '192.168.1.150', 'destination_ip' => '203.0.113.200', 'detected_at' => Carbon::now()->subHours(3), 'status' => 'new', 'confidence_score' => 0.97],
            ['attack_type' => 'Phishing Attempt', 'severity' => 'high', 'description' => 'Email with suspicious links detected. Potential phishing campaign.', 'source_ip' => '198.51.100.112', 'destination_ip' => '192.168.1.25', 'detected_at' => Carbon::now()->subHours(6), 'status' => 'investigating', 'confidence_score' => 0.91],
            ['attack_type' => 'Unauthorized Access', 'severity' => 'critical', 'description' => 'Unauthorized access to sensitive files detected. Immediate investigation required.', 'source_ip' => '192.168.1.180', 'destination_ip' => '192.168.1.30', 'detected_at' => Carbon::now()->subHours(1), 'status' => 'new', 'confidence_score' => 0.96],
            ['attack_type' => 'Data Exfiltration', 'severity' => 'critical', 'description' => 'Unusual large data transfer detected. Possible data theft in progress.', 'source_ip' => '192.168.1.95', 'destination_ip' => '203.0.113.150', 'detected_at' => Carbon::now()->subHours(4), 'status' => 'investigating', 'confidence_score' => 0.94],
            ['attack_type' => 'Suspicious DNS Query', 'severity' => 'low', 'description' => 'DNS query to known malicious domain detected.', 'source_ip' => '192.168.1.45', 'destination_ip' => '8.8.8.8', 'detected_at' => Carbon::now()->subHours(10), 'status' => 'resolved', 'confidence_score' => 0.76],
        ];

        $alertCount = 0;
        foreach ($alertsData as $index => $alertData) {
            $networkLog = $networkLogs[$index % count($networkLogs)];
            $mlModel = $mlModels[$index % count($mlModels)];
            
            // Use updateOrCreate to avoid duplicate errors
            Alert::updateOrCreate(
                [
                    'attack_type' => $alertData['attack_type'],
                    'source_ip' => $alertData['source_ip']
                ],
                array_merge($alertData, [
                    'network_log_id' => $networkLog->id,
                    'ml_model_id' => $mlModel->id,
                ])
            );
            $alertCount++;
        }

        $this->command->info('✓ Alerts created (' . $alertCount . ')');
        $this->command->info('');
        $this->command->info('✅ Test data seeded successfully!');
        $this->command->info('📊 Summary:');
        $this->command->info('   - 2 Users');
        $this->command->info('   - ' . count($networkLogs) . ' Network Logs');
        $this->command->info('   - ' . count($mlModels) . ' ML Models');
        $this->command->info('   - ' . $alertCount . ' Alerts');
        $this->command->info('');
        $this->command->info('🔑 Login credentials:');
        $this->command->info('   Email: admin@aiids.com');
        $this->command->info('   Password: password');
        $this->command->info('');
        $this->command->info('🔍 Try searching for:');
        $this->command->info('   - "Man-in-the-Middle"');
        $this->command->info('   - "DDoS"');
        $this->command->info('   - "Malware"');
        $this->command->info('   - "Critical"');
        $this->command->info('   - "Firewall"');
        $this->command->info('   - "Random Forest"');
    }
}
