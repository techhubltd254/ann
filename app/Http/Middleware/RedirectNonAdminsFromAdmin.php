<?php namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class RedirectNonAdminsFromAdmin {
    public function handle(Request $request, Closure $next) {
        $user = $request->user();
        if ($user && !in_array($user->account_type ?? '', ['superadmin', 'admin', 'ministry', 'county'])) {
            return redirect()->away('https://kicctest.org');
        }
        return $next($request);
    }
}