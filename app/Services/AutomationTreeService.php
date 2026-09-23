<?php

namespace App\Services;

use App\Models\AutomationRun;
use Illuminate\Support\Facades\Log;

/**
 * AutomationTreeService — the platform → sector → action automation tree.
 *
 * Previously this returned a display-only array: 18 trigger/action nodes and
 * zero execution calls. It is now executable — every leaf maps to real pipeline
 * ids and a real n8n workflow, and running a node records an AutomationRun and
 * cascades through the inter-pipeline bus.
 */
class AutomationTreeService
{
    public function __construct(private PipelineBusClient $bus) {}

    /** The tree, now carrying the pipeline ids and workflow each node drives. */
    public function getTree(): array
    {
        return [
            'name' => 'KICC Platform',
            'type' => 'platform',
            'children' => [
                [
                    'key' => 'county-content',
                    'name' => 'County Content Pipeline',
                    'file' => 'county-content-pipeline.json',
                    'type' => 'sector',
                    'schedule' => 'Weekly (every 7 days)',
                    'pipeline_ids' => [44, 45, 46],
                    'description' => 'Auto-fetch county tourism data, generate SEO-optimized content, push to platform',
                    'children' => [
                        ['name' => 'Trigger: Scheduled Scan', 'type' => 'trigger', 'icon' => 'clock'],
                        ['name' => 'Fetch County Content (API)', 'type' => 'action', 'icon' => 'download'],
                        ['name' => 'Generate SEO Metadata', 'type' => 'action', 'icon' => 'search'],
                        ['name' => 'Update Platform Content', 'type' => 'action', 'icon' => 'upload'],
                        ['name' => 'Notify County Admin', 'type' => 'action', 'icon' => 'bell'],
                    ],
                ],
                [
                    'key' => 'trade-promotion',
                    'name' => 'International Trade Promotion',
                    'file' => 'international-trade-promotion.json',
                    'type' => 'sector',
                    'schedule' => 'Weekly (Sunday 08:00)',
                    'pipeline_ids' => [1, 2, 3],
                    'description' => 'Scan new county products, generate multilingual trade listings, push to export partners',
                    'children' => [
                        ['name' => 'Schedule: Weekly Export Push', 'type' => 'trigger', 'icon' => 'clock'],
                        ['name' => 'Fetch New County Products', 'type' => 'action', 'icon' => 'package'],
                        ['name' => 'Generate Trade Listing (EN/FR/AR)', 'type' => 'action', 'icon' => 'globe'],
                        ['name' => 'Send to Export Partners', 'type' => 'action', 'icon' => 'send'],
                        ['name' => 'Log Promotion to Dashboard', 'type' => 'action', 'icon' => 'bar-chart'],
                    ],
                ],
                [
                    'key' => 'sbs',
                    'name' => 'Sector-by-Sector Pipeline',
                    'file' => 'sbs-pipeline.json',
                    'type' => 'sector',
                    'schedule' => 'Continuous (event-driven)',
                    'pipeline_ids' => [7, 9, 10],
                    'description' => 'Per-sector image analysis, video generation, content refresh — each sector runs independently',
                    'children' => [
                        ['name' => 'Trigger: Sector Update Event', 'type' => 'trigger', 'icon' => 'zap'],
                        ['name' => 'Run Sector Image Analyzer', 'type' => 'action', 'icon' => 'image'],
                        ['name' => 'Generate Sector Showcase Video', 'type' => 'action', 'icon' => 'video'],
                        ['name' => 'SEO Refresh for Sector', 'type' => 'action', 'icon' => 'refresh-cw'],
                        ['name' => 'Push to Screen Pipeline', 'type' => 'action', 'icon' => 'monitor'],
                    ],
                ],
                [
                    'key' => 'agentic-loop',
                    'name' => 'Agentic Loop (AI Automation)',
                    'file' => 'agentic-loop',
                    'type' => 'sector',
                    'schedule' => 'Every 15 min',
                    'pipeline_ids' => [32, 36],
                    'description' => 'Observe platform signals -> LLM decide -> Execute actions (SEO, content, alerts)',
                    'children' => [
                        ['name' => 'Observer: Collect Platform Signals', 'type' => 'process', 'icon' => 'eye'],
                        ['name' => 'Decider: LLM Evaluation', 'type' => 'process', 'icon' => 'cpu'],
                        ['name' => 'SEO Content Refresh', 'type' => 'action', 'icon' => 'file-text'],
                        ['name' => 'Recommendation Update', 'type' => 'action', 'icon' => 'star'],
                        ['name' => 'Admin Alert on Stuck Payments', 'type' => 'action', 'icon' => 'alert-triangle'],
                    ],
                ],
            ],
        ];
    }

    /** Every runnable node, flattened, for the admin control table. */
    public function nodes(): array
    {
        $out = [];
        foreach ($this->getTree()['children'] as $sector) {
            $out[] = [
                'key' => $sector['key'],
                'name' => $sector['name'],
                'schedule' => $sector['schedule'],
                'workflow' => $sector['file'],
                'pipeline_ids' => $sector['pipeline_ids'],
                'actions' => count($sector['children']),
            ];
        }

        return $out;
    }

    public function find(string $key): ?array
    {
        foreach ($this->nodes() as $n) {
            if ($n['key'] === $key) {
                return $n;
            }
        }

        return null;
    }

    /**
     * Execute one automation node: settle its pipelines and cascade into
     * everything downstream. Returns the AutomationRun that was recorded.
     */
    public function run(string $key, string $trigger = 'manual', ?int $userId = null): AutomationRun
    {
        $node = $this->find($key);
        if (! $node) {
            throw new \InvalidArgumentException("Unknown automation node [{$key}]");
        }

        $run = AutomationRun::create([
            'node_key' => $key,
            'node_name' => $node['name'],
            'workflow' => $node['workflow'],
            'status' => 'running',
            'trigger' => $trigger,
            'root_pipeline_ids' => $node['pipeline_ids'],
            'triggered_by' => $userId,
            'started_at' => now(),
        ]);

        try {
            $result = $this->bus->cascade($node['pipeline_ids']);

            if (($result['ok'] ?? false) === false) {
                throw new \RuntimeException((string) ($result['error'] ?? 'bus rejected the cascade'));
            }

            $settled = $result['pipelines'] ?? [];

            $run->update([
                'status' => 'succeeded',
                'settled_pipeline_ids' => $settled,
                'settled_count' => count($settled),
                'failed_pipeline_ids' => $result['failed'] ?? [],
                'dlq_count' => (int) ($result['dlq'] ?? 0),
                'correlation_id' => $result['metrics']['correlation'] ?? null,
                'duration_ms' => (int) ($result['metrics']['elapsed_ms'] ?? 0),
                'finished_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('automation: node failed', ['node' => $key, 'error' => $e->getMessage()]);
            $run->update(['status' => 'failed', 'error' => $e->getMessage(), 'finished_at' => now()]);
        }

        return $run->refresh();
    }
}
