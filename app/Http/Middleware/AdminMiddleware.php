<?php namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class AdminMiddleware {
    public function handle(Request $request, Closure $next, string $level = 'kicc') {
        $user = $request->user();
        if (!$user) return redirect()->route('login');
        $ok = match ($level) {
            'kicc' => $user->is_admin || $user->email === 'admin@kicc.go.ke',
            'county' => $user->is_admin || $user->county_admin || $user->county_id,
            'national' => $user->is_admin || $user->ministry_admin,
            'any' => $user->is_admin || $user->county_admin || $user->ministry_admin || $user->exhibitor || $user->provider,
            default => $user->is_admin,
        };
        if (!$ok) abort(403, 'Unauthorized.');
        return $next($request);
    }
}