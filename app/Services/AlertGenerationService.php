<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\NetworkLog;
use App\Notifications\SecurityAlertNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Exception;

class AlertGenerationService
{
    protected MLPredictionService $mlService;
    protected array $config;

    public function __construct(MLPredictionService $mlService)
    {
        $this->mlService = $mlService;
        $this->config = config('ml');
    }

    /**
     * Generate alert from ML prediction
     *
     * @param array $prediction
     * @param array $packetData
     * @param NetworkLog|null $networkLog
     * @return Alert|null
     */
    public function generateAlertFromPrediction(
        array $prediction,
        array $packetData,
        ?NetworkLog $networkLog = null
    ): ?Alert {
        try {
            // Check if prediction indicates an attack
            if (!$prediction['is_attack']) {
                return null;
            }

            // Check confidence threshold
            $threshold = $this->config['alert_threshold'] ?? 70;
            if ($prediction['confidence'] < $threshold) {
                Log::info('Prediction below threshold', [
                    'confidence' => $prediction['confidence'],
                    'threshold' => $threshold
                ]);
                return null;
            }

            // Create alert
            $alert = Alert::create([
                'attack_type' => $this->mapPredictionToAttackType($prediction),
                'severity' => $prediction['severity'],
                'severity_score' => $prediction['severity_score'],
                'confidence' => $prediction['confidence'],
                'source_ip' => $packetData['source_ip'] ?? 'unknown',
                'destination_ip' => $packetData['destination_ip'] ?? 'unknown',
                'source_port' => $packetData['src_port'] ?? null,
                'destination_port' => $packetData['dst_port'] ?? null,
                'protocol' => $this->getProtocolName($packetData['protocol'] ?? 0),
                'network_log_id' => $networkLog?->id,
                'status' => 'new',
                'description' => $this->generateDescription($prediction, $packetData),
                'evidence' => json_encode([
                    'prediction' => $prediction,
                    'packet_data' => $packetData,
                    'model_version' => $prediction['model_version'] ?? '1.0.0',
                    'timestamp' => now()->toISOString()
                ]),
                'detected_at' => now(),
            ]);

            Log::info('Alert generated from ML prediction', [
                'alert_id' => $alert->id,
                'severity' => $alert->severity,
                'confidence' => $alert->confidence
            ]);

            // Trigger notifications
            $this->sendNotifications($alert);

            // Trigger auto-response if configured
            $this->triggerAutoResponse($alert, $prediction);

            return $alert;

        } catch (Exception $e) {
            Log::error('Failed to generate alert from prediction', [
                'error' => $e->getMessage(),
                'prediction' => $prediction
            ]);

            return null;
        }
    }

    /**
     * Process packets and generate alerts
     *
     * @param array $packets
     * @param NetworkLog|null $networkLog
     * @return array
     */
    public function processPackets(array $packets, ?NetworkLog $networkLog = null): array
    {
        $alerts = [];
        $processed = 0;
        $alertsGenerated = 0;

        try {
            // Get predictions for all packets
            $predictions = $this->mlService->predictBatch($packets);

            foreach ($predictions as $index => $prediction) {
                $processed++;

                if ($prediction['is_attack']) {
                    $alert = $this->generateAlertFromPrediction(
                        $prediction,
                        $packets[$index],
                        $networkLog
                    );

                    if ($alert) {
                        $alerts[] = $alert;
                        $alertsGenerated++;
                    }
                }
            }

            Log::info('Batch packet processing completed', [
                'total_packets' => count($packets),
                'processed' => $processed,
                'alerts_generated' => $alertsGenerated
            ]);

        } catch (Exception $e) {
            Log::error('Batch packet processing failed', [
                'error' => $e->getMessage()
            ]);
        }

        return [
            'alerts' => $alerts,
            'stats' => [
                'processed' => $processed,
                'alerts_generated' => $alertsGenerated,
                'attack_rate' => $processed > 0 ? ($alertsGenerated / $processed) * 100 : 0
            ]
        ];
    }

    /**
     * Map prediction to attack type
     *
     * @param array $prediction
     * @return string
     */
    protected function mapPredictionToAttackType(array $prediction): string
    {
        $label = $prediction['prediction'] ?? 'unknown';

        $mapping = [
            'attack' => 'Network Attack',
            'dos' => 'DoS Attack',
            'probe' => 'Port Scan',
            'r2l' => 'Remote to Local Attack',
            'u2r' => 'User to Root Attack',
        ];

        return $mapping[$label] ?? 'Unknown Attack';
    }

    /**
     * Get protocol name from number
     *
     * @param int $protocol
     * @return string
     */
    protected function getProtocolName(int $protocol): string
    {
        $protocols = [
            1 => 'ICMP',
            6 => 'TCP',
            17 => 'UDP',
        ];

        return $protocols[$protocol] ?? 'Other';
    }

    /**
     * Generate alert description
     *
     * @param array $prediction
     * @param array $packetData
     * @return string
     */
    protected function generateDescription(array $prediction, array $packetData): string
    {
        $type = $this->mapPredictionToAttackType($prediction);
        $sourceIp = $packetData['source_ip'] ?? 'unknown';
        $destIp = $packetData['destination_ip'] ?? 'unknown';
        $confidence = round($prediction['confidence'], 2);

        return "Detected {$type} from {$sourceIp} to {$destIp} with {$confidence}% confidence. "
             . "Severity: {$prediction['severity']}. "
             . "Immediate investigation recommended.";
    }

    /**
     * Send notifications for alert
     *
     * @param Alert $alert
     * @return void
     */
    protected function sendNotifications(Alert $alert): void
    {
        try {
            $notifyConfig = $this->config['notifications'] ?? [];

            // Check if we should send email for this severity
            $shouldNotify = match($alert->severity) {
                'critical' => $notifyConfig['email_on_critical'] ?? true,
                'high' => $notifyConfig['email_on_high'] ?? true,
                'medium' => $notifyConfig['email_on_medium'] ?? false,
                default => false
            };

            if (!$shouldNotify) {
                return;
            }

            // Get recipients
            $recipients = $notifyConfig['email_recipients'] ?? '';
            if (empty($recipients)) {
                Log::info('No email recipients configured for alerts');
                return;
            }

            $emails = array_map('trim', explode(',', $recipients));

            // Send notification
            Notification::route('mail', $emails)
                ->notify(new SecurityAlertNotification($alert));

            Log::info('Alert notification sent', [
                'alert_id' => $alert->id,
                'recipients' => $emails
            ]);

        } catch (Exception $e) {
            Log::error('Failed to send alert notification', [
                'alert_id' => $alert->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Trigger auto-response actions
     *
     * @param Alert $alert
     * @param array $prediction
     * @return void
     */
    protected function triggerAutoResponse(Alert $alert, array $prediction): void
    {
        try {
            $autoResponse = $this->config['auto_response'] ?? [];

            // Check if auto-response is enabled
            if (!($autoResponse['enabled'] ?? false)) {
                return;
            }

            // Check severity threshold
            $minSeverity = $autoResponse['min_severity_score'] ?? 85;
            if ($alert->severity_score < $minSeverity) {
                return;
            }

            $actions = $autoResponse['actions'] ?? [];

            // Execute configured actions
            if ($actions['block_ip'] ?? false) {
                $this->blockIP($alert->source_ip);
            }

            if ($actions['isolate_host'] ?? false) {
                $this->isolateHost($alert->source_ip);
            }

            Log::info('Auto-response actions triggered', [
                'alert_id' => $alert->id,
                'source_ip' => $alert->source_ip,
                'actions' => array_keys(array_filter($actions))
            ]);

        } catch (Exception $e) {
            Log::error('Auto-response failed', [
                'alert_id' => $alert->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Block IP address
     *
     * @param string $ip
     * @return void
     */
    protected function blockIP(string $ip): void
    {
        // Placeholder for IP blocking logic
        // This would interface with firewall API or execute system commands
        Log::warning('IP block requested', ['ip' => $ip]);

        // Example: Add to blocked IPs table or call external API
        // BlockedIP::create(['ip_address' => $ip, 'reason' => 'Auto-response']);
    }

    /**
     * Isolate host
     *
     * @param string $ip
     * @return void
     */
    protected function isolateHost(string $ip): void
    {
        // Placeholder for host isolation logic
        Log::warning('Host isolation requested', ['ip' => $ip]);

        // Example: Call network management API to isolate host
    }
}
