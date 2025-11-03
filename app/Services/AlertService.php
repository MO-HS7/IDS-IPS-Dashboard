<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\User;
use App\Repositories\AlertRepository;
use App\Events\AlertCreated;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AlertService
{
    protected AlertRepository $alertRepository;

    public function __construct(AlertRepository $alertRepository)
    {
        $this->alertRepository = $alertRepository;
    }

    /**
     * Create a new alert with proper notification handling
     */
    public function createAlert(array $data): Alert
    {
        $alert = $this->alertRepository->create(array_merge($data, [
            'detected_at' => now(),
            'status' => 'new'
        ]));

        Log::info('Alert created successfully', ['id' => $alert->id]);

        // Fire event for async processing
        event(new AlertCreated($alert));

        return $alert;
    }

    /**
     * Get alert statistics for dashboard
     */
    public function getAlertStatistics(): array
    {
        return $this->alertRepository->getStatistics();
    }

    /**
     * Get recent alerts with proper eager loading
     */
    public function getRecentAlerts(int $limit = 5)
    {
        return $this->alertRepository->getRecent($limit)
            ->map(fn($alert) => [
                'id' => $alert->id,
                'attack_type' => $alert->attack_type ?? 'Unknown Attack',
                'severity' => $alert->severity ?? 'low',
                'detected_at' => $alert->detected_at ? $alert->detected_at->format('Y-m-d H:i') : now()->format('Y-m-d H:i'),
                'description' => $alert->description ?? '',
                'source_ip' => $alert->source_ip ?? 'N/A',
                'destination_ip' => $alert->destination_ip ?? 'N/A',
                'model_name' => $alert->mlModel->name ?? 'Unknown',
            ]);
    }
}
