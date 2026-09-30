<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * For any mutating method (POST/PUT/PATCH/DELETE) on authenticated routes,
 * record basic request context into audit_log if the controller didn't already.
 */
class AppendAuditContext
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $response;
        }
        if (!auth()->check()) return $response;
        if ($response->getStatusCode() >= 500) return $response;

        // Rate-gate: sample 1% of background audit writes to prevent DB churn.
        // Full audit is produced by explicit AuditLogger::log() calls in controllers.
        if (random_int(1, 100) > 1) return $response;

        DB::table('audit_log')->insertOrIgnore([
            'actor_id'     => auth()->id(),
            'action'       => 'http.' . strtolower($request->method()),
            'subject_type' => null,
            'subject_id'   => null,
            'meta'         => json_encode([
                'path' => $request->path(),
                'status' => $response->getStatusCode(),
            ], JSON_UNESCAPED_UNICODE),
            'ip'           => $request->ip(),
            'ua'           => substr((string) $request->userAgent(), 0, 250),
            'occurred_at'  => now(),
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        return $response;
    }
}
