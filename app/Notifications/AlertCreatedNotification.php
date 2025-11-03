<?php

namespace App\Notifications;

use App\Models\Alert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;    
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AlertCreatedNotification extends Notification implements ShouldQueue // إضافة implements ShouldQueue
{
    use Queueable;

    public $alert;

    public function __construct(Alert $alert)
    {
        $this->alert = $alert;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $severityEmoji = $this->getSeverityEmoji($this->alert->severity);
        $severityText = ucfirst($this->alert->severity);
        
        return (new MailMessage)
            ->subject("{$severityEmoji} New {$severityText} Security Alert Detected")
            ->greeting("Hello {$notifiable->name},")
            ->line("A new security threat has been detected in your network.")
            ->line("")
            ->line("**🚨 Alert Details:**")
            ->line("• **Attack Type:** {$this->alert->attack_type}")
            ->line("• **Severity Level:** {$severityText}")
            ->line("• **Detected At:** {$this->alert->detected_at->format('M d, Y H:i:s')}")
            ->line("• **Source IP:** " . ($this->alert->source_ip ?: 'Unknown'))
            ->when($this->alert->destination_ip, function ($mail) {
                return $mail->line("• **Target IP:** {$this->alert->destination_ip}");
            })
            ->when($this->alert->confidence_score, function ($mail) {
                $confidence = round($this->alert->confidence_score * 100, 1);
                return $mail->line("• **Confidence Score:** {$confidence}%");
            })
            ->when($this->alert->description, function ($mail) {
                return $mail->line("• **Description:** {$this->alert->description}");
            })
            ->line("")
            ->action('🔍 View Alert Details', url("/alerts/{$this->alert->id}"))
            ->line("")
            ->line('Please review this alert and take appropriate action if necessary.')
            ->line('Stay secure! 🛡️')
            ->salutation('Best regards,  
AI IDS Security Team');
    }

    public function toDatabase($notifiable)
    {
        return [
            'alert_id' => $this->alert->id,
            'type' => 'alert_created',
            'title' => 'New Security Alert',
            'message' => "New {$this->alert->severity} threat detected: {$this->alert->attack_type}",
            'severity' => $this->alert->severity,
            'attack_type' => $this->alert->attack_type,
            'detected_at' => $this->alert->detected_at->toISOString(),
            'action_url' => "/alerts/{$this->alert->id}",
            'icon' => $this->getSeverityEmoji($this->alert->severity),
            'color' => $this->getSeverityColor($this->alert->severity)
        ];
    }

    private function getSeverityEmoji($severity)
    {
        return match($severity) {
            'critical' => '🔴',
            'high' => '🟠',
            'medium' => '🟡',
            'low' => '🟢',
            default => '⚪'
        };
    }

    private function getSeverityColor($severity)
    {
        return match($severity) {
            'critical' => 'red',
            'high' => 'orange',
            'medium' => 'yellow',
            'low' => 'green',
            default => 'gray'
        };
    }
}
