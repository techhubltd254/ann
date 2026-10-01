<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

/**
 * Prometheus-compatible metrics endpoint for Grafana dashboards.
 * GET /api/metrics — scraped by Prometheus every 15s.
 *
 * Panels:
 *   - kicc_bus_lag: event count − max ack_offset per consumer
 *   - kicc_queue_depth: Horizon queue sizes
 *   - kicc_http_errors: 500 rate from recent log
 *   - kicc_active_users: users logged in last 15 min
 *   - kicc_tidb_connections: SHOW STATUS
 */
class MetricsController extends Controller
{
    public function index(): JsonResponse
    {
        $metrics = [];

        // ── Bus lag per consumer group ──
        try {
            $totalEvents = DB::table('bus_events')->count();
            $offsets = DB::table('consumer_offsets')->get(['consumer_group', 'ack_offset', 'processed_count', 'dlq_count']);
            $busLag = [];
            foreach ($offsets as $o) {
                $busLag[$o->consumer_group] = [
                    'lag' => max(0, $totalEvents - $o->ack_offset),
                    'processed' => $o->processed_count,
                    'dlq' => $o->dlq_count,
                ];
            }
            $metrics['bus'] = ['total_events' => $totalEvents, 'consumers' => $busLag];
        } catch (\Throwable $e) {
            $metrics['bus'] = ['error' => $e->getMessage()];
        }

        // ── Queue depth ──
        try {
            $metrics['queue'] = [
                'default_size'  => DB::table('jobs')->where('queue', 'default')->count(),
                'video_size'    => DB::table('jobs')->where('queue', 'video')->count(),
                'sync_size'     => DB::table('jobs')->where('queue', 'sync')->count(),
                'failed'        => DB::table('failed_jobs')->count(),
            ];
        } catch (\Throwable $e) {
            $metrics['queue'] = ['error' => $e->getMessage()];
        }

        // ── Error rate (last 15 min, from cache) ──
        $errors = Cache::get('metrics:http_5xx_count', 0);
        $metrics['errors'] = ['http_5xx_15m' => $errors];

        // ── Active users (last 15 min) ──
        $activeUsers = Cache::get('metrics:active_users', 0);
        $metrics['users'] = ['active_15m' => $activeUsers];

        // ── TiDB connections ──
        try {
            $connResult = DB::select("SHOW STATUS LIKE 'Threads_connected'");
            $metrics['tidb'] = ['threads_connected' => (int) ($connResult[0]->Value ?? 0)];
        } catch (\Throwable) {
            $metrics['tidb'] = ['threads_connected' => -1];
        }

        return response()->json([
            'service' => 'kicc-platform',
            'version' => '1.0',
            'timestamp' => now()->toIso8601String(),
            'metrics' => $metrics,
        ]);
    }
}