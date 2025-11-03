<?php

use App\Http\Controllers\NetworkLogController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\MLModelController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\LiveMonitoringController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // API v1 routes with prefix & names to avoid conflicts
    Route::prefix('v1')->as('api.v1.')->group(function () {
        // Dashboard API
        Route::get('dashboard', [\App\Http\Controllers\Api\V1\DashboardController::class, 'index'])->name('dashboard.index');
        Route::get('dashboard/statistics', [\App\Http\Controllers\Api\V1\DashboardController::class, 'statistics'])->name('dashboard.statistics');
        Route::get('dashboard/charts', [\App\Http\Controllers\Api\V1\DashboardController::class, 'charts'])->name('dashboard.charts');
        Route::get('dashboard/system-health', [\App\Http\Controllers\Api\V1\DashboardController::class, 'systemHealth'])->name('dashboard.system-health');
        
        // Alerts API
        Route::apiResource('alerts', \App\Http\Controllers\Api\V1\AlertController::class);
        Route::get('alerts/statistics', [\App\Http\Controllers\Api\V1\AlertController::class, 'statistics'])->name('alerts.statistics');
        
        // Network Logs API (using existing controller for now)
        Route::apiResource('network-logs', NetworkLogController::class);
        
        // ML Models API (using existing controller for now)
        Route::apiResource('ml-models', MLModelController::class);
    });
    
    // Global Search API Routes
    Route::prefix('search')->group(function () {
        Route::get('/global', [SearchController::class, 'global'])->name('search.global');
    });
    
    // Module-Specific Search Routes
    Route::prefix('network-logs')->group(function () {
        Route::get('/search', [SearchController::class, 'networkLogs'])->name('search.network-logs');
    });
    
    Route::prefix('alerts')->group(function () {
        Route::get('/search', [SearchController::class, 'alerts'])->name('search.alerts');
    });
    
    Route::prefix('ml-models')->group(function () {
        Route::get('/search', [SearchController::class, 'mlModels'])->name('search.ml-models');
    });
    
    Route::prefix('analytics')->group(function () {
        Route::get('/search', [SearchController::class, 'analytics'])->name('search.analytics');
    });
    
    Route::prefix('notifications')->group(function () {
        Route::get('/search', [SearchController::class, 'notifications'])->name('search.notifications');
    });
    
    Route::prefix('users')->group(function () {
        Route::get('/search', [SearchController::class, 'users'])->name('search.users');
    });
    
    // NOTE: Live Monitoring and PCAP routes have been moved to web.php
    // to use web session authentication instead of Sanctum tokens.
    // This is required for Inertia.js compatibility.
});
