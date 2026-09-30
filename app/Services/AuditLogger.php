<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

/**
 * Append-only audit log.
 * Every state-changing admin action writes one row here.
 * Audit rows cannot be deleted or updated; only inserts.
 */
class AuditLogger
{
    public static function log(
        ?int $actorId,
        string $action,
        ?string $subjectType = null,
        ?int $subjectId = null,
        array $meta = []
    ): int {
        return (int) DB::table('audit_log')->insertGetId([
            'actor_id'     => $actorId,
            'action'       => $action,
            'subject_type' => $subjectType,
            'subject_id'   => $subjectId,
            'meta'         => json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'ip'           => Request::ip(),
            'ua'           => substr((string) Request::userAgent(), 0, 250),
            'occurred_at'  => now(),
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }
}
