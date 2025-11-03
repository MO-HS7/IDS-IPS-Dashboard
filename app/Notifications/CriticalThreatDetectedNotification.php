<?php

namespace App\Notifications;

use App\Models\Alert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue; // إضافة
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CriticalThreatDetectedNotification extends Notification implements ShouldQueue // إضافة implements ShouldQueue
{
    use Queueable;

    public $alert;

    public function __construct(Alert $alert)
    {
        $this->alert = $alert;
    }

    // باقي الكود كما هو...
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject("🚨🚨 CRITICAL SECURITY THREAT DETECTED - IMMEDIATE ACTION REQUIRED 🚨🚨")
            ->greeting("⚠️ URGENT ALERT - {$notifiable->name}")
            ->line("**A CRITICAL security threat has been detected and requires immediate attention.**")
            ->line("")
            ->line("**🔥 CRITICAL THREAT DETAILS:**")
            ->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━")
            ->line("🔴 **Attack Type:** {$this->alert->attack_type}")
            ->line("🔴 **Severity Level:** CRITICAL")
            ->line("🔴 **Detected At:** {$this->alert->detected_at->format('M d, Y H:i:s')}")
            ->line("🔴 **Source IP:** " . ($this->alert->source_ip ?: 'Unknown'))
            ->when($this->alert->destination_ip, function ($mail) {
                return $mail->line("🔴 **Target IP:** {$this->alert->destination_ip}");
            })
            ->when($this->alert->confidence_score, function ($mail) {
                $confidence = round($this->alert->confidence_score * 100, 1);
                return $mail->line("🔴 **Confidence Score:** {$confidence}%");
            })
            ->when($this->alert->description, function ($mail) {
                return $mail->line("🔴 **Description:** {$this->alert->description}");
            })
            ->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━")
            ->line("")
            ->line("**⚠️ IMMEDIATE ACTIONS REQUIRED:**")
            ->line("1. 🔍 Review the alert details immediately")
            ->line("2. 🚫 Consider blocking the source IP address")
            ->line("3. 📊 Analyze network traffic patterns")
            ->line("4. 🛡️ Check firewall and security rules")
            ->line("5. 📞 Notify your security team if needed")
            ->line("")
            ->action('🚨 INVESTIGATE CRITICAL THREAT NOW', url("/alerts/{$this->alert->id}"))
            ->line("")
            ->error()
            ->line("**This is an automated critical security alert.**")
            ->line("**Response time is critical for system security.**")
            ->salutation('🛡️ AI IDS Security Team  
⚠️ Critical Alert System');
    }

    public function toDatabase($notifiable)
    {
        return [
            'alert_id' => $this->alert->id,
            'type' => 'critical_threat',
            'title' => '🚨 CRITICAL THREAT DETECTED',
            'message' => "URGENT: Critical {$this->alert->attack_type} attack detected from " . ($this->alert->source_ip ?: 'unknown source'),
            'severity' => 'critical',
            'attack_type' => $this->alert->attack_type,
            'detected_at' => $this->alert->detected_at->toISOString(),
            'action_url' => "/alerts/{$this->alert->id}",
            'icon' => '🚨',
            'color' => 'red',
            'priority' => 'urgent'
        ];
    }
}
