<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Notifications\DatabaseNotification;
use Carbon\Carbon;

class NotificationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        
        if (!$admin) {
            $this->command->error('Admin user not found. Please seed users first.');
            return;
        }

        $this->command->info('Creating notifications for admin user...');

        $notifications = [
            [
                'type' => 'alert',
                'title' => 'Critical Security Alert',
                'message' => 'Multiple failed login attempts detected from IP 192.168.1.100',
                'severity' => 'critical',
                'icon' => '🚨',
                'color' => 'red',
                'action_url' => '/alerts',
                'priority' => 'high',
                'created_at' => Carbon::now()->subMinutes(5),
                'read_at' => null
            ],
            [
                'type' => 'threat',
                'title' => 'Potential DDoS Attack',
                'message' => 'Unusual traffic spike detected from multiple sources',
                'severity' => 'high',
                'icon' => '🛡️',
                'color' => 'red',
                'action_url' => '/network-analysis/live-monitoring',
                'priority' => 'high',
                'created_at' => Carbon::now()->subMinutes(15),
                'read_at' => null
            ],
            [
                'type' => 'success',
                'title' => 'ML Model Training Complete',
                'message' => 'Random Forest model trained successfully with 92.5% accuracy',
                'severity' => 'low',
                'icon' => '✅',
                'color' => 'green',
                'action_url' => '/ml-models',
                'priority' => 'normal',
                'created_at' => Carbon::now()->subHour(),
                'read_at' => Carbon::now()->subMinutes(30)
            ],
            [
                'type' => 'info',
                'title' => 'PCAP File Processed',
                'message' => 'network_traffic_20231024.pcap has been analyzed. 10,000 packets processed.',
                'severity' => 'low',
                'icon' => 'ℹ️',
                'color' => 'blue',
                'action_url' => '/network-logs',
                'priority' => 'normal',
                'created_at' => Carbon::now()->subHours(2),
                'read_at' => Carbon::now()->subMinutes(45)
            ],
            [
                'type' => 'warning',
                'title' => 'High CPU Usage Detected',
                'message' => 'System CPU usage has exceeded 85% for the last 10 minutes',
                'severity' => 'medium',
                'icon' => '⚠️',
                'color' => 'yellow',
                'action_url' => '/dashboard',
                'priority' => 'normal',
                'created_at' => Carbon::now()->subHours(3),
                'read_at' => null
            ],
            [
                'type' => 'alert',
                'title' => 'SQL Injection Attempt',
                'message' => 'Blocked SQL injection attempt on login form from IP 203.0.113.45',
                'severity' => 'high',
                'icon' => '🚨',
                'color' => 'red',
                'action_url' => '/alerts',
                'priority' => 'high',
                'created_at' => Carbon::now()->subHours(4),
                'read_at' => Carbon::now()->subHours(3)
            ],
            [
                'type' => 'system',
                'title' => 'System Update Available',
                'message' => 'A new version of the IDS system is available for update',
                'severity' => 'low',
                'icon' => '⚙️',
                'color' => 'gray',
                'action_url' => '/settings',
                'priority' => 'low',
                'created_at' => Carbon::now()->subHours(6),
                'read_at' => null
            ],
            [
                'type' => 'success',
                'title' => 'Backup Completed',
                'message' => 'Database backup completed successfully. Size: 245 MB',
                'severity' => 'low',
                'icon' => '✅',
                'color' => 'green',
                'action_url' => null,
                'priority' => 'low',
                'created_at' => Carbon::now()->subHours(8),
                'read_at' => Carbon::now()->subHours(7)
            ],
            [
                'type' => 'threat',
                'title' => 'Port Scan Detected',
                'message' => 'Port scanning activity detected from IP 198.51.100.23',
                'severity' => 'medium',
                'icon' => '🛡️',
                'color' => 'orange',
                'action_url' => '/alerts',
                'priority' => 'normal',
                'created_at' => Carbon::now()->subHours(12),
                'read_at' => Carbon::now()->subHours(11)
            ],
            [
                'type' => 'info',
                'title' => 'New User Registered',
                'message' => 'A new analyst user has been registered: john.doe@example.com',
                'severity' => 'low',
                'icon' => 'ℹ️',
                'color' => 'blue',
                'action_url' => '/users',
                'priority' => 'low',
                'created_at' => Carbon::now()->subDay(),
                'read_at' => Carbon::now()->subHours(20)
            ],
            [
                'type' => 'warning',
                'title' => 'Disk Space Low',
                'message' => 'Available disk space is below 20%. Please free up some space.',
                'severity' => 'medium',
                'icon' => '⚠️',
                'color' => 'yellow',
                'action_url' => null,
                'priority' => 'normal',
                'created_at' => Carbon::now()->subDays(2),
                'read_at' => null
            ],
            [
                'type' => 'alert',
                'title' => 'Brute Force Attack Blocked',
                'message' => 'Blocked 500+ login attempts from IP range 192.168.2.0/24',
                'severity' => 'critical',
                'icon' => '🚨',
                'color' => 'red',
                'action_url' => '/alerts',
                'priority' => 'high',
                'created_at' => Carbon::now()->subDays(3),
                'read_at' => Carbon::now()->subDays(2)
            ]
        ];

        foreach ($notifications as $notificationData) {
            $createdAt = $notificationData['created_at'];
            $readAt = $notificationData['read_at'];
            
            unset($notificationData['created_at'], $notificationData['read_at']);

            $notification = new DatabaseNotification([
                'id' => \Illuminate\Support\Str::uuid(),
                'type' => 'App\Notifications\SystemNotification',
                'notifiable_type' => User::class,
                'notifiable_id' => $admin->id,
                'data' => $notificationData,
                'read_at' => $readAt,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            $notification->save();
        }

        $this->command->info('✓ Created ' . count($notifications) . ' notifications successfully!');
    }
}
