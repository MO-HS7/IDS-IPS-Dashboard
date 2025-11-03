<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RateLimitUploads
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = 'uploads:' . $request->ip();
        
        // Allow configurable uploads per minute per IP
        $maxAttempts = config('ids.rate_limiting.uploads_per_minute', 5);
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            
            return response()->json([
                'message' => 'Too many upload attempts. Please try again in ' . $seconds . ' seconds.',
                'retry_after' => $seconds
            ], 429);
        }
        
        RateLimiter::hit($key, 60); // 1 minute decay
        
        return $next($request);
    }
}
