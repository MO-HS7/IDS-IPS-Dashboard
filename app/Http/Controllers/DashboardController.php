<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\NetworkLog;
use App\Models\User;
use App\Models\MLModel;
use App\Services\DashboardService;
use App\Services\AlertService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

/**
 * Class DashboardController
 *
 * Handles the data and statistics for the application dashboard,
 * including alerts, attack types, severity distribution, and system health.
 *
 * @package App\Http\Controllers
 */
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
     * Display the main dashboard with statistics and recent alerts.
     *
     * @OA\Get(
     *     path="/dashboard",
     *     summary="Get dashboard statistics and recent alerts",
     *     tags={"Dashboard"},
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard data returned successfully"
     *     )
     * )
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        // Use service layer for better organization and caching
        $statistics = $this->dashboardService->getDashboardStats();
        $systemHealth = $this->dashboardService->getSystemHealth();
        // Defer heavy datasets to async endpoints to keep initial payload light
        $chartData = [
            'attackTypeDistribution' => collect([]),
            'alertsOverTime' => collect([]),
            'severityDistribution' => collect([]),
        ];
        // Keep recent alerts minimal
        $recentAlerts = $this->alertService->getRecentAlerts(5);

        return Inertia::render('Dashboard', [
            'statistics' => $statistics,
            'attackTypeDistribution' => $chartData['attackTypeDistribution']->values(),
            'alertsOverTime' => $chartData['alertsOverTime']->values(),
            'severityDistribution' => $chartData['severityDistribution']->values(),
            'systemHealth' => $systemHealth,
            'recentAlerts' => $recentAlerts,
        ]);
    }

    public function stats(): JsonResponse
    {
        $statistics = $this->dashboardService->getDashboardStats();
        return response()->json([
            'success' => true,
            'data' => $statistics,
        ]);
    }

    public function charts(Request $request): JsonResponse
    {
        $charts = $this->dashboardService->getChartData();
        return response()->json([
            'success' => true,
            'data' => [
                'attackTypeDistribution' => $charts['attackTypeDistribution']->values(),
                'alertsOverTime' => $charts['alertsOverTime']->values(),
                'severityDistribution' => $charts['severityDistribution']->values(),
            ],
        ]);
    }

    public function recentAlerts(Request $request): JsonResponse
    {
        $limit = (int) $request->query('limit', 5);
        $limit = max(1, min(50, $limit));
        $alerts = $this->alertService->getRecentAlerts($limit);
        return response()->json([
            'success' => true,
            'data' => $alerts,
        ]);
    }

}
