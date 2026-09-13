<?php namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware {
    public function handle(Request $request, Closure $next, string $level = 'kicc')
    {
        $user = $request->user();
        if (!$user) return redirect()->route('login');

        $ok = match ($level) {
            'kicc' => $user->hasRole('kicc_admin'),
            'county' => $user->hasRole('kicc_admin') || $user->hasRole('national_admin') || $user->hasRole('county_admin'),
            'national' => $user->hasRole('kicc_admin') || $user->hasRole('national_admin'),
            'any' => $user->hasAnyRole(['kicc_admin', 'national_admin', 'county_admin', 'exhibitor', 'provider']),
            default => $user->hasRole('kicc_admin'),
        };

        if (!$ok) abort(403, 'Unauthorized.');
        return $next($request);
    }
}