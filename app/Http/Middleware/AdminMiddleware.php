<?php namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class AdminMiddleware {
    public function handle(Request $request, Closure $next, string $level = 'kicc') {
        $user = $request->user();
        if (!$user) return redirect()->route('login');

        $type = $user->account_type ?? '';
        $ok = match ($level) {
            'kicc' => $type === 'superadmin' || $user->email === 'admin@kicc.go.ke',
            'county' => $type === 'superadmin' || $type === 'county' || $user->county_id,
            'national' => $type === 'superadmin' || $type === 'admin' || $type === 'ministry',
            'any' => in_array($type, ['superadmin','admin','ministry','county','exhibitor','provider']),
            default => $type === 'superadmin',
        };
        if (!$ok) abort(403, 'Unauthorized.');
        return $next($request);
    }
}