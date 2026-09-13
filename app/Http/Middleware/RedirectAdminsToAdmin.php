<?php namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class RedirectAdminsToAdmin {
    public function handle(Request $request, Closure $next) {
        $user = $request->user();
        if ($user && $user->hasAnyRole(['kicc_admin', 'national_admin', 'county_admin'])) {
            $path = $request->path();
            if (in_array($path, ['login', 'login-code', 'register', 'logout', 'mfa/challenge'])) {
                return $next($request);
            }
            return redirect('/portal');
        }
        return $next($request);
    }
}