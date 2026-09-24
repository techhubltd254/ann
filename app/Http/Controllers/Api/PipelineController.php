<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AutomationRun;
use App\Services\PipelineBusClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PipelineController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'video' => 'required|file|mimes:mp4,mov,avi,mkv|max:2048',
            'pipeline' => 'nullable|in:sbs,splat,hybrid',
            'depth_strength' => 'nullable|numeric|min:0.5|max:3.0',
            'convergence' => 'nullable|numeric|min:0.0|max:1.0',
            'callback_url' => 'nullable|url',
        ]);

        $video = $request->file('video');
        $path = $video->store('uploads', 'local');

        $payload = [
            'user_id' => $request->user()?->id,
            'video_path' => $path,
            'pipeline' => $request->input('pipeline', 'sbs'),
            'depth_strength' => $request->float('depth_strength', 1.5),
            'convergence' => $request->float('convergence', 0.3),
            'callback_url' => $request->input('callback_url'),
            'status' => 'queued',
        ];

        Log::info('Pipeline job queued', $payload);

        return response()->json([
            'message' => 'Video uploaded and queued for processing',
            'job' => $payload,
        ], 201);
    }

    public function status(Request $request, string $jobId)
    {
        return response()->json([
            'job_id' => $jobId,
            'status' => 'processing',
        ]);
    }

    // ── Inter-pipeline automation bus (monitor + control) ──

    /** The 87-pipeline dependency graph. */
    public function graph(): JsonResponse
    {
        return response()->json(app(PipelineBusClient::class)->graph());
    }

    /** Aggregate bus metrics for the Mother Admin monitoring page. */
    public function busStatus(): JsonResponse
    {
        return response()->json(app(PipelineBusClient::class)->status());
    }

    public function ledger(Request $request): JsonResponse
    {
        return response()->json(app(PipelineBusClient::class)->ledger((int) $request->integer('limit', 100)));
    }

    public function dlq(Request $request): JsonResponse
    {
        return response()->json(app(PipelineBusClient::class)->dlq((int) $request->integer('limit', 100)));
    }

    /** CONTROL — settle roots and cascade into every dependent pipeline. */
    public function cascade(Request $request): JsonResponse
    {
        $roots = (array) $request->input('roots', []);
        if ($roots === []) {
            return response()->json(['ok' => false, 'error' => 'roots required'], 422);
        }

        $result = app(PipelineBusClient::class)->cascade($roots);

        AutomationRun::create([
            'node_key' => 'api-cascade',
            'node_name' => 'API cascade',
            'status' => ($result['ok'] ?? false) ? 'succeeded' : 'failed',
            'trigger' => 'api',
            'root_pipeline_ids' => $roots,
            'settled_pipeline_ids' => $result['pipelines'] ?? [],
            'settled_count' => count($result['pipelines'] ?? []),
            'failed_pipeline_ids' => $result['failed'] ?? [],
            'dlq_count' => (int) ($result['dlq'] ?? 0),
            'duration_ms' => (int) ($result['metrics']['elapsed_ms'] ?? 0),
            'triggered_by' => optional($request->user())->id,
            'started_at' => now(),
            'finished_at' => now(),
        ]);

        return response()->json($result);
    }

    /** CONTROL — trigger one pipeline directly (also used to retry the DLQ). */
    public function trigger(Request $request): JsonResponse
    {
        $id = (int) $request->integer('pipeline_id');
        if ($id < 1) {
            return response()->json(['ok' => false, 'error' => 'pipeline_id required'], 422);
        }

        return response()->json(app(PipelineBusClient::class)->trigger($id, (array) $request->input('payload', [])));
    }

    /**
     * EARN — called after a bus cascade to turn the settled pipelines into real
     * DB revenue (escrow → pool → ledger). This is the bridge between the
     * automation bus ledger and the Mother Pool.
     */
    public function earnSettled(Request $request): JsonResponse
    {
        // Verify the integration secret (bus calls this without a Sanctum token)
        $secret = config('kicc.integration_webhook_secret', 'dev-secret');
        if ($request->header('X-Integration-Secret') !== $secret) {
            return response()->json(['ok' => false, 'error' => 'invalid secret'], 401);
        }

        $ids = (array) $request->input('pipeline_ids', []);

        if ($ids === []) {
            return response()->json(['ok' => false, 'error' => 'pipeline_ids required'], 422);
        }

        // Resolve pipeline IDs → codes
        $codes = \Illuminate\Support\Facades\DB::table('pipeline_registrations')
            ->whereIn('id', array_map('intval', $ids))
            ->where(function ($q) {
                $q->whereNull('earning_locked')->orWhere('earning_locked', 0);
            })
            ->pluck('code')
            ->values()
            ->toArray();

        if ($codes === []) {
            return response()->json(['ok' => true, 'earned' => 0, 'pipeline_codes' => []]);
        }

        // Run the earn engine on these pipelines
        $earn = new \App\Console\Commands\PipelineEarn();
        // Use reflection to invoke the internal processor per code
        $ref = new \ReflectionClass($earn);
        $method = $ref->getMethod('processOne');
        $method->setAccessible(true);

        $results = [];
        foreach ($codes as $code) {
            try {
                $results[] = $method->invoke($earn, $code, false);
            } catch (\Throwable $e) {
                Log::warning('pipeline-earn: single failed', ['code' => $code, 'error' => $e->getMessage()]);
            }
        }

        $earned = count(array_filter($results, fn ($r) => $r['settled'] ?? false));

        return response()->json([
            'ok' => true,
            'earned' => $earned,
            'pipeline_codes' => $codes,
            'gmv' => array_sum(array_column($results, 'gmv')),
            'results' => $results,
        ]);
    }
}
