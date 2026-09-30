<?php

namespace App\Console\Commands;

use App\Services\AuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReplayDlq extends Command
{
    protected $signature = 'kicc:replay-dlq {--limit=200}';
    protected $description = 'Re-queue bus DLQ entries that reached max retries.';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $rows = DB::table('bus_dlq')->where('attempts', '<=', DB::raw('max_attempts'))->limit($limit)->get();

        $replayed = 0;
        foreach ($rows as $dlq) {
            DB::transaction(function () use ($dlq, &$replayed) {
                DB::table('bus_events')->insert([
                    'event_type'      => $dlq->event_type,
                    'payload'         => $dlq->payload,
                    'occurred_at'     => now(),
                    'attempts'        => 0,
                    'idempotency_key' => 'replay-' . $dlq->event_key . '-' . now()->timestamp,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
                DB::table('bus_dlq')->where('id', $dlq->id)->update(['attempts' => $dlq->attempts + 1]);
                AuditLogger::log(null, 'bus.dlq.replayed', null, $dlq->id, []);
                $replayed++;
            });
        }
        $this->info("Replayed {$replayed} DLQ entries.");
        return self::SUCCESS;
    }
}
