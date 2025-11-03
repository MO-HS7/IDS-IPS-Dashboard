<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Services\AlertService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    protected DashboardService $dashboardService;
    protected AlertService $alertService;

    public function __construct(DashboardService $dashboardService, AlertService $alertService)
    {
        $this->dashboardService = $dashboardService;
        $this->alertService = $alertService;
    }

    /**
     * Get dashboard statistics and data.
     */
    public function index(): JsonResponse
    {
        $stats = $this->dashboardService->getDashboardStats();
        $chartData = $this->dashboardService->getChartData();
        $systemHealth = $this->dashboardService->getSystemHealth();
        $recentAlerts = $this->alertService->getRecentAlerts();

        return response()->json([
            'success' => true,
            'data' => [
                'statistics' => $stats,
                'charts' => $chartData,
                'system_health' => $systemHealth,
                'recent_alerts' => $recentAlerts,
            ]
        ]);
    }

    /**
     * Get dashboard statistics only.
     */
    public function statistics(): JsonResponse
    {
        $stats = $this->dashboardService->getDashboardStats();

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get chart data only.
     */
    public function charts(): JsonResponse
    {
        $chartData = $this->dashboardService->getChartData();

        return response()->json([
            'success' => true,
            'data' => $chartData
        ]);
    }

    /**
     * Get system health metrics.
     */
    public function systemHealth(): JsonResponse
    {
        $systemHealth = $this->dashboardService->getSystemHealth();

        return response()->json([
            'success' => true,
            'data' => $systemHealth
        ]);
    }
}
