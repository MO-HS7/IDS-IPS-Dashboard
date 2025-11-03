<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\NetworkLog;
use App\Models\MLModel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SearchController extends Controller
{
    /**
     * Global search across all modules
     */
    public function global(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        
        if (strlen($query) < 2) {
            return response()->json(['results' => []]);
        }

        $results = [];

        // Search Alerts
        if (Auth::user()->can('viewAny', Alert::class)) {
            $alerts = Alert::where('alert_type', 'like', "%{$query}%")
                ->orWhere('severity', 'like', "%{$query}%")
                ->orWhere('description', 'like', "%{$query}%")
                ->limit(5)
                ->get()
                ->map(function ($alert) {
                    return [
                        'type' => 'alert',
                        'title' => $alert->alert_type,
                        'description' => $alert->description,
                        'severity' => $alert->severity,
                        'timestamp' => $alert->detected_at ? Carbon::parse($alert->detected_at)->diffForHumans() : null,
                        'url' => "/alerts/{$alert->id}"
                    ];
                });
            
            $results = array_merge($results, $alerts->toArray());
        }

        // Search Network Logs
        if (Auth::user()->can('viewAny', NetworkLog::class)) {
            $logs = NetworkLog::where('file_name', 'like', "%{$query}%")
                ->orWhere('description', 'like', "%{$query}%")
                ->limit(5)
                ->get()
                ->map(function ($log) {
                    return [
                        'type' => 'network-log',
                        'title' => $log->file_name,
                        'description' => $log->description ?? 'Network log file',
                        'timestamp' => $log->upload_date ? Carbon::parse($log->upload_date)->diffForHumans() : null,
                        'url' => "/network-logs"
                    ];
                });
            
            $results = array_merge($results, $logs->toArray());
        }

        // Search ML Models
        $models = MLModel::where('name', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->limit(5)
            ->get()
            ->map(function ($model) {
                return [
                    'type' => 'ml-model',
                    'title' => $model->name,
                    'description' => $model->description,
                    'timestamp' => $model->created_at->diffForHumans(),
                    'url' => "/ml-models"
                ];
            });
        
        $results = array_merge($results, $models->toArray());

        // Search Users (Admin only)
        if (Auth::user()->can('viewAny', User::class)) {
            $users = User::where('name', 'like', "%{$query}%")
                ->orWhere('email', 'like', "%{$query}%")
                ->limit(5)
                ->get()
                ->map(function ($user) {
                    return [
                        'type' => 'user',
                        'title' => $user->name,
                        'description' => $user->email,
                        'timestamp' => $user->created_at->diffForHumans(),
                        'url' => "/users"
                    ];
                });
            
            $results = array_merge($results, $users->toArray());
        }

        return response()->json([
            'results' => array_slice($results, 0, 10) // Limit to 10 results
        ]);
    }

    /**
     * Search within Network Logs
     */
    public function networkLogs(Request $request): JsonResponse
    {
        $this->authorize('viewAny', NetworkLog::class);

        $query = $request->input('q', '');
        
        if (strlen($query) < 2) {
            return response()->json(['results' => []]);
        }

        $results = NetworkLog::where('file_name', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->orWhere('status', 'like', "%{$query}%")
            ->limit(10)
            ->get()
            ->map(function ($log) {
                return [
                    'type' => 'network-log',
                    'title' => $log->file_name,
                    'description' => $log->description ?? "Uploaded by {$log->user->name}",
                    'timestamp' => Carbon::parse($log->upload_date)->format('M d, Y H:i'),
                    'url' => "/network-logs"
                ];
            });

        return response()->json(['results' => $results]);
    }

    /**
     * Search within Alerts
     */
    public function alerts(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Alert::class);

        $query = $request->input('q', '');
        
        if (strlen($query) < 2) {
            return response()->json(['results' => []]);
        }

        $results = Alert::where('alert_type', 'like', "%{$query}%")
            ->orWhere('severity', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->orWhere('source_ip', 'like', "%{$query}%")
            ->orWhere('destination_ip', 'like', "%{$query}%")
            ->limit(10)
            ->get()
            ->map(function ($alert) {
                return [
                    'type' => 'alert',
                    'title' => $alert->alert_type,
                    'description' => $alert->description,
                    'severity' => $alert->severity,
                    'timestamp' => Carbon::parse($alert->detected_at)->format('M d, Y H:i'),
                    'url' => "/alerts/{$alert->id}"
                ];
            });

        return response()->json(['results' => $results]);
    }

    /**
     * Search within ML Models
     */
    public function mlModels(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        
        if (strlen($query) < 2) {
            return response()->json(['results' => []]);
        }

        $results = MLModel::where('name', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->orWhere('algorithm', 'like', "%{$query}%")
            ->limit(10)
            ->get()
            ->map(function ($model) {
                return [
                    'type' => 'ml-model',
                    'title' => $model->name,
                    'description' => $model->description ?? "Algorithm: {$model->algorithm}",
                    'timestamp' => $model->trained_at ? Carbon::parse($model->trained_at)->format('M d, Y') : 'Not trained',
                    'url' => "/ml-models"
                ];
            });

        return response()->json(['results' => $results]);
    }

    /**
     * Search within Analytics
     */
    public function analytics(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        
        if (strlen($query) < 2) {
            return response()->json(['results' => []]);
        }

        // Since analytics is a view/report page, search within alerts and logs
        $alertResults = Alert::where('alert_type', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->limit(5)
            ->get()
            ->map(function ($alert) {
                return [
                    'type' => 'analytics',
                    'title' => "Alert: {$alert->alert_type}",
                    'description' => $alert->description,
                    'severity' => $alert->severity,
                    'timestamp' => Carbon::parse($alert->detected_at)->format('M d, Y'),
                    'url' => "/analytics"
                ];
            });

        return response()->json(['results' => $alertResults]);
    }

    /**
     * Search within Notifications
     */
    public function notifications(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        
        if (strlen($query) < 2) {
            return response()->json(['results' => []]);
        }

        $results = Auth::user()->notifications()
            ->where('data', 'like', "%{$query}%")
            ->limit(10)
            ->get()
            ->map(function ($notification) {
                $data = is_string($notification->data) ? json_decode($notification->data, true) : $notification->data;
                
                return [
                    'type' => 'notification',
                    'title' => $data['title'] ?? 'Notification',
                    'description' => $data['message'] ?? $data['description'] ?? '',
                    'timestamp' => $notification->created_at->diffForHumans(),
                    'url' => "/notifications"
                ];
            });

        return response()->json(['results' => $results]);
    }

    /**
     * Search within Users (Admin only)
     */
    public function users(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $query = $request->input('q', '');
        
        if (strlen($query) < 2) {
            return response()->json(['results' => []]);
        }

        $results = User::where('name', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->orWhere('role', 'like', "%{$query}%")
            ->limit(10)
            ->get()
            ->map(function ($user) {
                return [
                    'type' => 'user',
                    'title' => $user->name,
                    'description' => "{$user->email} • {$user->role}",
                    'timestamp' => "Joined " . $user->created_at->format('M Y'),
                    'url' => "/users"
                ];
            });

        return response()->json(['results' => $results]);
    }
}
