<?php namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;

class VerifyN8nWebhook {
    public function handle(Request $request, Closure $next) {
        $signature = $request->header('X-N8N-Signature');
        $secret = config('services.n8n.webhook_secret');
        if (!$secret) return $next($request); // no secret configured = skip check
        $payload = $request->getContent();
        $expected = hash_hmac('sha256', $payload, $secret);
        if (!hash_equals($expected, $signature)) {
            abort(401, 'Invalid n8n webhook signature');
        }
        return $next($request);
    }
}