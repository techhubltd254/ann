<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Content Security Policy
        $response->headers->set('Content-Security-Policy',
            "default-src 'self' *.cloudflarestream.com cloudflarestream.com *.workers.dev media.kicctest.org kicctest.org *.kicctest.org; " .
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' cdn.tailwindcss.com cdn.jsdelivr.net unpkg.com www.googletagmanager.com googletagmanager.com accounts.google.com *.google.com *.gstatic.com; " .
            "style-src 'self' 'unsafe-inline' cdn.tailwindcss.com fonts.googleapis.com *.googleapis.com; " .
            "font-src 'self' fonts.gstatic.com data:; " .
            "img-src 'self' data: blob: *.cloudflarestream.com *.workers.dev media.kicctest.org *.r2.cloudflarestorage.com *.google.com *.gstatic.com www.google-analytics.com google-analytics.com; " .
            "media-src 'self' blob: data: *.cloudflarestream.com *.workers.dev media.kicctest.org *.r2.cloudflarestorage.com; " .
            "connect-src 'self' kicctest.org *.kicctest.org *.cloudflarestream.com cloudflarestream.com *.workers.dev media.kicctest.org wss://* ws://* cdn.jsdelivr.net accounts.google.com *.google.com www.google-analytics.com google-analytics.com *.googletagmanager.com; " .
            "frame-ancestors 'self'; " .
            "base-uri 'self'; " .
            "form-action 'self' accounts.google.com"
        );

        // HSTS — force HTTPS
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');

        // Frame protect
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Permissions-Policy', 'camera=(self), microphone=(self), geolocation=self');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-site');

        // Cache control for sensitive endpoints
        if ($request->is('api/*') || $request->is('kicc-live/admin/*') || $request->is('kicc-live/studio/*')) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
        }

        return $response;
    }
}