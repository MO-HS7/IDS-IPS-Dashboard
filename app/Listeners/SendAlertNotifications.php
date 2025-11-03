<?php

namespace App\Listeners;

use App\Events\AlertCreated;
use App\Models\User;
use App\Notifications\AlertCreatedNotification;
use App\Notifications\CriticalThreatDetectedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendAlertNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(AlertCreated $event): void
    {
        $alert = $event->alert;
        
        Log::info('Processing alert notifications for alert ID: ' . $alert->id);

        try {
            // Send critical threat notifications
            if ($alert->severity === 'critical') {
                $adminsAndAnalysts = User::whereIn('role', ['Admin', 'Analyst'])->get();
                foreach ($adminsAndAnalysts as $user) {
                    $user->notify(new CriticalThreatDetectedNotification($alert));
                }
            }

            // Send role-based notifications
            $this->sendRoleBasedNotifications($alert);

            Log::info('Alert notifications sent successfully for alert ID: ' . $alert->id);
        } catch (\Exception $e) {
            Log::error('Failed to send alert notifications: ' . $e->getMessage());
            throw $e; // Re-throw to trigger retry mechanism
        }
    }

    /**
     * Send notifications based on alert severity and user roles
     */
    private function sendRoleBasedNotifications($alert): void
    {
        $notificationRules = [
            'critical' => ['Admin', 'Analyst'],
            'high' => ['Admin', 'Analyst'],
            'medium' => ['Admin', 'Analyst'],
            'low' => ['Admin']
        ];

        $targetRoles = $notificationRules[$alert->severity] ?? ['Admin'];
        $targetUsers = User::whereIn('role', $targetRoles)->get();

        foreach ($targetUsers as $user) {
            try {
                $user->notify(new AlertCreatedNotification($alert));
            } catch (\Exception $e) {
                Log::error("Failed to send notification to {$user->email}: " . $e->getMessage());
            }
        }
    }
}
