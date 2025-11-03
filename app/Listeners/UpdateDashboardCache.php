<?php

namespace App\Listeners;

use App\Events\AlertCreated;
use App\Events\NetworkLogProcessed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class UpdateDashboardCache implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle alert created event.
     */
    public function handleAlertCreated(AlertCreated $event): void
    {
        Log::info('Updating dashboard cache after alert creation');
        
        // Clear dashboard caches to force refresh
        Cache::forget('dashboard_stats');
        Cache::forget('dashboard_charts');
        
        Log::info('Dashboard cache cleared after alert creation');
    }

    /**
     * Handle network log processed event.
     */
    public function handleNetworkLogProcessed(NetworkLogProcessed $event): void
    {
        Log::info('Updating dashboard cache after network log processing');
        
        // Clear dashboard caches to force refresh
        Cache::forget('dashboard_stats');
        Cache::forget('dashboard_charts');
        
        Log::info('Dashboard cache cleared after network log processing');
    }
}
