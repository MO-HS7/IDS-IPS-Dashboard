<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\NetworkLog;
use App\Models\User;
use App\Models\MLModel;
use App\Repositories\AlertRepository;
use App\Repositories\NetworkLogRepository;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class DashboardService
{
    protected AlertRepository $alertRepository;
    protected NetworkLogRepository $networkLogRepository;

    public function __construct(AlertRepository $alertRepository, NetworkLogRepository $networkLogRepository)
    {
        $this->alertRepository = $alertRepository;
        $this->networkLogRepository = $networkLogRepository;
    }

    /**
     * Get dashboard statistics with caching
     */
    public function getDashboardStats(): array
    {
        return Cache::remember('dashboard_stats', 300, function () { // 5 minutes cache
            $alertStats = $this->alertRepository->getStatistics();
            $networkLogStats = $this->networkLogRepository->getStatistics();

            return [
                'total_logs' => $networkLogStats['total_logs'],
                'total_alerts' => $alertStats['total_alerts'] ?? 0,
                'critical_alerts' => $alertStats['critical_alerts'] ?? 0,
                'pending_logs' => $networkLogStats['pending_logs'],
                'active_models' => MLModel::count(),
                'total_users' => User::count(),
            ];
        });
    }

    /**
     * Get chart data with caching
     */
    public function getChartData(): array
    {
        return Cache::remember('dashboard_charts', 180, function () { // 3 minutes cache
            $now = Carbon::now();
            
            return [
                'attackTypeDistribution' => $this->getAttackTypeDistribution($now),
                'alertsOverTime' => $this->getAlertsOverTime(),
                'severityDistribution' => $this->getSeverityDistribution(),
            ];
        });
    }

    /**
     * Get attack type distribution for the last 30 days
     */
    private function getAttackTypeDistribution(Carbon $now)
    {
        // Limit to top 10 attack types within the last 30 days
        $distribution = $this->alertRepository->getAttackTypeDistribution($now->copy()->subDays(30), 10)
            ->map(fn($item) => ['name' => $item->attack_type, 'value' => $item->count]);

        // Return real data (user has data now)
        return $distribution->isNotEmpty() ? $distribution : collect([]);
    }

    /**
     * Get alerts over the last 7 days
     */
    private function getAlertsOverTime()
    {
        $alertsOverTime = collect();
        $period = CarbonPeriod::create(now()->subDays(6)->startOfDay(), now()->endOfDay());
        
        foreach ($period as $date) {
            $count = Alert::whereDate('detected_at', $date->format('Y-m-d'))->count();
            $alertsOverTime->push([
                'date' => $date->format('Y-m-d'),
                'day' => $date->format('M d'),
                'count' => $count
            ]);
        }
        
        // If no data, return actual data instead of dummy (user has real data now)
        return $alertsOverTime;
    }

    /**
     * Get severity distribution
     */
    private function getSeverityDistribution()
    {
        $distribution = $this->alertRepository->getSeverityDistribution()
            ->map(fn($item) => [
                'severity' => ucfirst($item->severity),
                'count' => $item->count,
                'color' => $this->getSeverityColor($item->severity)
            ]);

        // Return real data (user has data now)
        return $distribution->isNotEmpty() ? $distribution : collect([]);
    }

    /**
     * Get system health metrics
     */
    public function getSystemHealth(): array
    {
        return [
            'models_active' => MLModel::count(),
            'logs_processed_today' => $this->networkLogRepository->getProcessedToday(),
            'alerts_today' => Alert::whereDate('detected_at', now())->count(),
            'avg_processing_time' => config('ids.dashboard.avg_processing_time', '2.3s'),
        ];
    }

    /**
     * Get color code based on alert severity
     */
    private function getSeverityColor(string $severity): string
    {
        return match (strtolower($severity)) {
            'critical' => '#ef4444',
            'high' => '#f97316',
            'medium' => '#eab308',
            'low' => '#22c55e',
            default => '#6b7280'
        };
    }

    /**
     * Get dummy data for charts when no real data exists
     */
    private function getDummyAttackTypeData()
    {
        return collect(config('ids.dashboard.default_chart_data.attack_types', []));
    }

    private function getDummyAlertsOverTimeData()
    {
        return collect([
            ['date' => now()->subDays(6)->format('Y-m-d'), 'day' => now()->subDays(6)->format('M d'), 'count' => 5],
            ['date' => now()->subDays(5)->format('Y-m-d'), 'day' => now()->subDays(5)->format('M d'), 'count' => 8],
            ['date' => now()->subDays(4)->format('Y-m-d'), 'day' => now()->subDays(4)->format('M d'), 'count' => 12],
            ['date' => now()->subDays(3)->format('Y-m-d'), 'day' => now()->subDays(3)->format('M d'), 'count' => 7],
            ['date' => now()->subDays(2)->format('Y-m-d'), 'day' => now()->subDays(2)->format('M d'), 'count' => 15],
            ['date' => now()->subDays(1)->format('Y-m-d'), 'day' => now()->subDays(1)->format('M d'), 'count' => 10],
            ['date' => now()->format('Y-m-d'), 'day' => now()->format('M d'), 'count' => 6],
        ]);
    }

    private function getDummySeverityData()
    {
        return collect(config('ids.dashboard.default_chart_data.severity_distribution', []));
    }
}
