<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ThrottleApi
{
    private array $limits = [
        'api/live/heartbeat' => [10, 5],      // 10 requests per 5 seconds per booth
        'api/live/meeting-book' => [5, 60],     // 5 bookings per minute per user
        'api/live/favourite' => [10, 10],       // 10 toggles per 10 seconds
        'api/live/booths/active' => [30, 60],   // 30 refreshes per minute
        'default' => [60, 60],                  // 60 requests per minute per IP
    ];

    public function handle(Request $request, Closure $next)
    {
        $route = $request->path();
        $ip = $request->ip();
        $userId = auth()->id() ?? 'anon_' . $ip;
        $boothId = $request->input('booth_id') ?? $request->route('booth')?->id ?? 'global';

        // Determine limit
        $limit = $this->limits[$route] ?? $this->limits['default'];
        $key = "throttle:{$route}:{$userId}:{$boothId}";

        $current = Cache::get($key, 0);
        if ($current >= $limit[0]) {
            Log::warning('API throttle triggered', [
                'route' => $route,
                'user' => $userId,
                'booth_id' => $boothId,
                'ip' => $ip,
            ]);
            return response()->json([
                'error' => 'Too many requests. Please wait.',
                'retry_after' => $limit[1],
            ], 429);
        }

        Cache::put($key, $current + 1, $limit[1]);

        return $next($request);
    }
}