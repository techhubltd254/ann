<?php namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;

class VerifyN8nWebhook {
    public function handle(Request $request, Closure $next) {
        $signature = $request->header('X-N8N-Signature');
        $secret = config('services.n8n.webhook_secret');
        if (!is_string($secret)||strlen($secret)<32) abort(503,'Webhook verification is not provisioned');
        $ts=(int)$request->header('X-N8N-Timestamp');$nonce=(string)$request->header('X-N8N-Nonce');if(abs(time()-$ts)>300||!preg_match('/^[A-Za-z0-9_-]{16,128}$/',$nonce))abort(401);
        $payload=$ts.'.'.$nonce.'.'.$request->getContent();
        $expected = hash_hmac('sha256', $payload, $secret);
        if (!hash_equals($expected, (string)$signature)) {
            abort(401, 'Invalid n8n webhook signature');
        }
        if(!\Illuminate\Support\Facades\Cache::add('n8n-replay:'.hash('sha256',$nonce),1,600))abort(409);
        return $next($request);
    }
}