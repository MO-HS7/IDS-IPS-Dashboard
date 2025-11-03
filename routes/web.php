<?php

use App\Http\Controllers\AlertController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MLModelController;
use App\Http\Controllers\NetworkLogController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\LiveMonitoringController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Landing Page (Public)
|--------------------------------------------------------------------------
*/
Route::get('/', [LandingController::class, 'index'])->name('landing');

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| Resend Email Verification Route
|--------------------------------------------------------------------------
*/
Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('status', 'verification-link-sent');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

/*
|--------------------------------------------------------------------------
| Protected Routes (Authenticated & Verified)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Analytics Route مع فلتر اختياري (query string)
    // مثال: /analytics?filter=last_7_days
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');

    Route::get('/analytics/export', [AnalyticsController::class, 'export'])->name('analytics.export');

    // Profile Management
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Update Password
    Route::put('/password', [PasswordController::class, 'update'])->name('password.update');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/api/notifications', [NotificationController::class, 'getNotifications'])->name('notifications.api');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::delete('/notifications/clear-all', [NotificationController::class, 'destroyAll'])->name('notifications.destroy-all');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile.update');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password.update');

    // Dashboard lightweight API (async fetch on client to avoid heavy initial payload)
    Route::prefix('api/dashboard')->group(function () {
        Route::get('/stats', [DashboardController::class, 'stats'])->name('api.dashboard.stats');
        Route::get('/charts', [DashboardController::class, 'charts'])->name('api.dashboard.charts');
        Route::get('/recent-alerts', [DashboardController::class, 'recentAlerts'])->name('api.dashboard.recent-alerts');
    });

    /*
    |--------------------------------------------------------------------------
    | Admin & Analyst Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware(['check.role:Admin,Analyst'])->group(function () {
        // Rate limit file uploads to prevent abuse
        Route::middleware(['throttle.uploads'])->group(function () {
            Route::post('network-logs', [NetworkLogController::class, 'store']);
        });
        
        Route::resource('network-logs', NetworkLogController::class)->except(['store']);
        Route::get('network-logs/{networkLog}/view', [NetworkLogController::class, 'showLog'])->name('network-logs.view');
        Route::resource('alerts', AlertController::class);
        
        // Live Monitoring Routes
        Route::get('live-monitoring', [LiveMonitoringController::class, 'index'])->name('live-monitoring.index');
        
        // Live Monitoring API Routes (moved from api.php for web session auth)
        Route::prefix('api/live-monitoring')->group(function () {
            Route::get('/interfaces', [LiveMonitoringController::class, 'getInterfaces'])->name('api.live-monitoring.interfaces');
            Route::post('/start', [LiveMonitoringController::class, 'start'])->name('api.live-monitoring.start');
            Route::post('/stop', [LiveMonitoringController::class, 'stop'])->name('api.live-monitoring.stop');
            Route::get('/poll', [LiveMonitoringController::class, 'pollPackets'])->name('api.live-monitoring.poll');
            Route::get('/status/{sessionId}', [LiveMonitoringController::class, 'status'])->name('api.live-monitoring.status');
            Route::get('/history', [LiveMonitoringController::class, 'history'])->name('api.live-monitoring.history');
            Route::delete('/{sessionId}', [LiveMonitoringController::class, 'destroy'])->name('api.live-monitoring.destroy');
        });
        
        // PCAP File Processing API Routes (moved from api.php for web session auth)
        Route::prefix('api/pcap')->group(function () {
            Route::post('/preview', [NetworkLogController::class, 'preview'])->name('api.pcap.preview');
            Route::get('/progress/{networkLog}', [NetworkLogController::class, 'progress'])->name('api.pcap.progress');
            Route::post('/retry/{networkLog}', [NetworkLogController::class, 'retry'])->name('api.pcap.retry');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Admin Only Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware(['check.role:Admin'])->group(function () {
        Route::resource('ml-models', MLModelController::class);
        Route::resource('users', UserController::class);
        Route::get('system-health', [\App\Http\Controllers\SystemHealthController::class, 'index'])->name('system-health.index');
        
        // ML Model Training Routes
        Route::get('ml-models/{ml_model}/train', [MLModelController::class, 'train'])->name('ml-models.train');
        
        // ML Model Training API Routes (for AJAX)
        Route::prefix('api/ml-models')->group(function () {
            Route::post('{ml_model}/start-training', [MLModelController::class, 'startTraining'])->name('api.ml-models.start-training');
            Route::get('{ml_model}/training-status/{sessionId}', [MLModelController::class, 'trainingStatus'])->name('api.ml-models.training-status');
            Route::get('{ml_model}/metrics', [MLModelController::class, 'metrics'])->name('api.ml-models.metrics');
            Route::post('{ml_model}/activate', [MLModelController::class, 'activate'])->name('api.ml-models.activate');
            Route::get('{ml_model}/predictions', [MLModelController::class, 'predictions'])->name('api.ml-models.predictions');
            Route::get('types', [MLModelController::class, 'modelTypes'])->name('api.ml-models.types');
        });
    });

});

/*
|--------------------------------------------------------------------------
| Public Webhook Endpoints (Python Scripts)
|--------------------------------------------------------------------------
| These routes are outside auth middleware for Python script access
*/
Route::post('/api/live-monitoring/packet', [LiveMonitoringController::class, 'receivePacket'])
    ->name('api.live-monitoring.packet');

Route::post('/api/pcap/process-packet', [NetworkLogController::class, 'processPacket'])
    ->name('api.pcap.process-packet');
