<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CachePublicResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        if (str_starts_with('/' . $request->path(), '/media/')) {
            return $response;
        }
        if (!str_contains($response->headers->get('Content-Type', ''), 'text/html')) {
            return $response;
        }
        // Rendered pages contain session-specific CSRF tokens and may include
        // administrative controls. Shared caching would leak personalized HTML
        // and continue serving outdated layouts after a deployment.
        // This does not affect static asset or R2 media cache policies.
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        $response->headers->set('CDN-Cache-Control', 'no-store');
        $response->headers->set('Cloudflare-CDN-Cache-Control', 'no-store');
        $response->headers->set('X-Kicc-Cache', 'BYPASS-SESSION-HTML');
        return $response;
    }
}
