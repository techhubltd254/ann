<?php namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class RedirectAdminsToAdmin {
    public function handle(Request $request, Closure $next) {
        $user = $request->user();
        if ($user && in_array($user->account_type ?? '', ['superadmin', 'admin', 'ministry', 'county'])) {
            // Skip redirect for login/logout routes to avoid loops
            $path = $request->path();
            if (in_array($path, ['login', 'login-code', 'register', 'logout', 'mfa/challenge'])) {
                return $next($request);
            }
            return redirect()->away('https://admin.kicctest.org/login');
        }
        return $next($request);
    }
}