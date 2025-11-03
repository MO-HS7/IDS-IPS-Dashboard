<?php

namespace App\Http\Controllers;


use App\Models\Alert;
use App\Models\NetworkLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function index(Request $request): Response
    {
        try {
            $period = $request->get('period', '30d');
            $validPeriods = ['7d', '30d', '90d', '1y'];
            if (!in_array($period, $validPeriods)) {
                $period = '30d';
            }

            $alertsData = $this->getAlertsAnalytics($period);
            $networkData = $this->getNetworkAnalytics($period);
            $threatData = $this->getThreatAnalytics($period);

            if ($request->has('period') && $request->period !== '30d') {
                $periodLabels = [
                    '7d' => 'Last 7 Days',
                    '30d' => 'Last 30 Days',
                    '90d' => 'Last 90 Days',
                    '1y' => 'Last Year'
                ];
                session()->flash('info', "Analytics updated for period: {$periodLabels[$period]}");
            }

            return Inertia::render('Analytics', [
                'alertsData' => $alertsData,
                'networkData' => $networkData,
                'threatData' => $threatData,
                'currentPeriod' => $period,
                'pageTitle' => 'Analytics Dashboard',
            ]);
        } catch (\Exception $e) {
            return Inertia::render('Analytics', [
                'alertsData' => [],
                'networkData' => [],
                'threatData' => [],
                'currentPeriod' => $request->get('period', '30d'),
                'pageTitle' => 'Analytics Dashboard',
                'error' => 'Failed to load analytics data: ' . $e->getMessage(),
            ]);
        }
    }

    private function getAlertsAnalytics($period)
    {
        $days = $this->getDaysFromPeriod($period);
        $startDate = now()->subDays($days);

        // Compatible with SQLite - use strftime instead of DATE_FORMAT
        $monthlyAlerts = Alert::query()
            ->select(DB::raw("strftime('%Y-%m', detected_at) as month"), DB::raw('count(*) as total'))
            ->where('detected_at', '>=', $startDate)
            ->groupBy('month')
            ->get()
            ->pluck('total', 'month');

        $criticalAlerts = Alert::query()
            ->select(DB::raw("strftime('%Y-%m', detected_at) as month"), DB::raw('count(*) as total'))
            ->where('severity', 'critical')
            ->where('detected_at', '>=', $startDate)
            ->groupBy('month')
            ->get()
            ->pluck('total', 'month');

        $severityDistribution = Alert::query()
            ->select('severity', DB::raw('count(*) as total'))
            ->where('detected_at', '>=', $startDate)
            ->groupBy('severity')
            ->get()
            ->pluck('total', 'severity');

        return [
            'monthly' => [
                'labels' => $monthlyAlerts->keys()->toArray(),
                'datasets' => [
                    [
                        'label' => 'Total Alerts',
                        'data' => array_values($monthlyAlerts->values()->toArray()),
                        'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                        'borderColor' => 'rgba(59, 130, 246, 1)',
                        'borderWidth' => 2,
                    ],
                    [
                        'label' => 'Critical Alerts',
                        'data' => array_values($criticalAlerts->values()->toArray()),
                        'backgroundColor' => 'rgba(239, 68, 68, 0.5)',
                        'borderColor' => 'rgba(239, 68, 68, 1)',
                        'borderWidth' => 2,
                    ],
                ],
            ],
            'severity' => [
                'labels' => $severityDistribution->keys()->toArray(),
                'datasets' => [
                    [
                        'data' => array_values($severityDistribution->values()->toArray()),
                        'backgroundColor' => [
                            'rgba(239, 68, 68, 0.8)',
                            'rgba(245, 158, 11, 0.8)',
                            'rgba(234, 179, 8, 0.8)',
                            'rgba(34, 197, 94, 0.8)',
                        ],
                        'borderColor' => [
                            'rgba(239, 68, 68, 1)',
                            'rgba(245, 158, 11, 1)',
                            'rgba(234, 179, 8, 1)',
                            'rgba(34, 197, 94, 1)',
                        ],
                        'borderWidth' => 2,
                    ],
                ],
            ],
        ];
    }

    private function getNetworkAnalytics($period)
    {
        $days = $this->getDaysFromPeriod($period);
        $startDate = now()->subDays($days);

        // Compatible with SQLite - use strftime
        $traffic = NetworkLog::query()
            ->select(DB::raw("strftime('%Y-%m-%d %H:00:00', created_at) as hour"), DB::raw('COALESCE(sum(file_size), 0) as total_size'))
            ->where('created_at', '>=', $startDate)
            ->groupBy('hour')
            ->get();

        return [
            'traffic' => [
                'labels' => $traffic->pluck('hour')->toArray(),
                'datasets' => [
                    [
                        'label' => 'Incoming Traffic (Bytes)',
                        'data' => array_values($traffic->pluck('total_size')->toArray()),
                        'borderColor' => 'rgba(34, 197, 94, 1)',
                        'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                        'fill' => true,
                        'tension' => 0.4,
                        'borderWidth' => 3,
                    ],
                ],
            ],
        ];
    }

    private function getThreatAnalytics($period)
    {
        $days = $this->getDaysFromPeriod($period);
        $startDate = now()->subDays($days);

        $threats = Alert::query()
            ->select('attack_type', DB::raw('count(*) as total'))
            ->where('detected_at', '>=', $startDate)
            ->groupBy('attack_type')
            ->get();

        return [
            'types' => [
                'labels' => $threats->pluck('attack_type')->toArray(),
                'datasets' => [
                    [
                        'label' => 'Threat Count',
                        'data' => array_values($threats->pluck('total')->toArray()),
                        'backgroundColor' => [
                            'rgba(239, 68, 68, 0.8)',
                            'rgba(245, 158, 11, 0.8)',
                            'rgba(59, 130, 246, 0.8)',
                            'rgba(34, 197, 94, 0.8)',
                            'rgba(168, 85, 247, 0.8)',
                            'rgba(236, 72, 153, 0.8)',
                        ],
                        'borderColor' => [
                            'rgba(239, 68, 68, 1)',
                            'rgba(245, 158, 11, 1)',
                            'rgba(59, 130, 246, 1)',
                            'rgba(34, 197, 94, 1)',
                            'rgba(168, 85, 247, 1)',
                            'rgba(236, 72, 153, 1)',
                        ],
                        'borderWidth' => 2,
                    ],
                ],
            ],
        ];
    }

    private function getDaysFromPeriod($period)
    {
        return match ($period) {
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            '1y' => 365,
            default => 30,
        };
    }

    public function export(Request $request)
    {
        try {
            $period = $request->get('period', '30d');
            $format = $request->get('format', 'csv');

            $alertsData = $this->getAlertsAnalytics($period);
            $networkData = $this->getNetworkAnalytics($period);
            $threatData = $this->getThreatAnalytics($period);

            $filename = "analytics_report_{$period}_" . now()->format('Y_m_d_H_i_s');

            if ($format === 'csv') {
                return $this->exportToCsv($alertsData, $networkData, $threatData, $filename);
            } else if ($format === 'json') {
                return $this->exportToJson($alertsData, $networkData, $threatData, $filename);
            }

            return redirect()->back()
                ->with('error', 'Unsupported export format.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to export analytics: ' . $e->getMessage());
        }
    }

    private function exportToCsv($alertsData, $networkData, $threatData, $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}.csv\"",
        ];

        return response()->stream(function () use ($alertsData, $networkData, $threatData) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['AI-IDS Analytics Report - Generated on ' . now()->format('Y-m-d H:i:s')]);
            fputcsv($handle, []);

            fputcsv($handle, ['ALERTS ANALYTICS']);
            fputcsv($handle, ['Month', 'Total Alerts', 'Critical Alerts']);

            foreach ($alertsData['monthly']['labels'] as $index => $month) {
                fputcsv($handle, [
                    $month,
                    $alertsData['monthly']['datasets'][0]['data'][$index] ?? 0,
                    $alertsData['monthly']['datasets'][1]['data'][$index] ?? 0,
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['ALERT SEVERITY DISTRIBUTION']);
            fputcsv($handle, ['Severity', 'Count']);

            foreach ($alertsData['severity']['labels'] as $index => $severity) {
                fputcsv($handle, [
                    $severity,
                    $alertsData['severity']['datasets'][0]['data'][$index] ?? 0,
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['THREAT TYPES']);
            fputcsv($handle, ['Threat Type', 'Count']);

            foreach ($threatData['types']['labels'] as $index => $threat) {
                fputcsv($handle, [
                    $threat,
                    $threatData['types']['datasets'][0]['data'][$index] ?? 0,
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    private function exportToJson($alertsData, $networkData, $threatData, $filename)
    {
        $data = [
            'generated_at' => now()->format('Y-m-d H:i:s'),
            'alerts' => $alertsData,
            'network' => $networkData,
            'threats' => $threatData,
        ];

        return response()->json($data)
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}.json\"");
    }
}