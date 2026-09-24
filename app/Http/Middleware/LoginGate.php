<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * LoginGate — gates marketplace e-commerce pages behind authentication.
 * Like Amazon/Kilimall: visitors see product listings but must log in to
 * view prices, add to cart, checkout, or place orders.
 */
class LoginGate
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            // Allow these actions without login
            $path = $request->path();
            $allowed = [
                'marketplace', 'marketplace/', 'marketplace/*',
                'login', 'register', 'forgot-password', 'reset-password',
                'api/counties', 'api/mcp', 'api/webhooks/*',
            ];

            foreach ($allowed as $pattern) {
                $pattern = str_replace('*', '.*', $pattern);
                if (preg_match("#^{$pattern}$#", $path)) {
                    return $next($request);
                }
            }

            // Block cart, checkout, orders, wishlist, dashboard without login
            if (preg_match('#^(cart|checkout|orders|wishlist|dashboard|api/pipeline)#', $path)) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Authentication required.'], 401);
                }
                return redirect()->route('login')->with('warning', 'Please sign in to continue.');
            }
        }

        return $next($request);
    }
}