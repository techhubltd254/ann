<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MonitorQueue extends Command
{
    protected $signature = 'queue:monitor {--threshold=500 : Alert when jobs exceed this count}';

    protected $description = 'Monitor queue depth and alert via n8n if backlogged';

    public function handle(): int
    {
        $threshold = (int) $this->option('threshold');
        $count = DB::table('jobs')->count();

        $this->info("Queue depth: {$count} jobs");

        if ($count <= $threshold) {
            return 0;
        }

        $this->warn("⚠️ Queue exceeded threshold: {$count} > {$threshold}");

        // Log to system
        Log::warning("Queue depth alert: {$count} jobs pending");

        // Fire n8n webhook
        try {
            $base = rtrim(config('services.n8n.base_url', env('N8N_BASE_URL', '')), '/');
            if ($base) {
                Http::timeout(5)->post("{$base}/webhook/kicc-queue-alert", [
                    'event' => 'queue_alert',
                    'jobs' => $count,
                    'threshold' => $threshold,
                    'time' => now()->toIso8601String(),
                ]);
                $this->info('n8n alert fired');
            }
        } catch (\Throwable $e) {
            Log::warning('queue monitor n8n alert failed: ' . $e->getMessage());
        }

        return 0;
    }
}