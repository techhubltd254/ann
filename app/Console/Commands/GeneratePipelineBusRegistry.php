<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Generate the pipeline-bus registry files (pipelines.json + integration-map.json)
 * from the live pipeline_registrations table, so the Node bus mirrors the DB.
 * Run after deploys:  php artisan kicc:bus-registry
 */
class GeneratePipelineBusRegistry extends Command
{
    protected $signature = 'kicc:bus-registry {--path= : Base path to write files (defaults to base_path())}';

    protected $description = 'Generate pipelines.json + integration-map.json for the inter-pipeline automation bus';

    public function handle(): int
    {
        $path = rtrim($this->option('path') ?: base_path(), '/');

        $all = DB::table('pipeline_registrations')->orderBy('id')->get([
            'id', 'code', 'sector', 'phase', 'status', 'slug', 'parent', 'economics',
        ]);

        $codeToId = [];
        foreach ($all as $r) {
            $codeToId[$r->code] = (int) $r->id;
        }

        $sectorDesk = [
            'trade' => 'marketplace-desk', 'agriculture' => 'agro-desk',
            'energy' => 'energy-desk', 'investment' => 'investment-desk',
            'tourism' => 'tourism-desk', 'financing' => 'financing-desk',
            'government' => 'gov-desk', 'health' => 'health-desk',
            'education' => 'education-desk', 'creative' => 'creative-desk',
            'mobility' => 'mobility-desk', 'identity' => 'identity-desk',
            'milk-dairy' => 'dairy-desk',
        ];

        $pipelines = [];
        foreach ($all as $r) {
            $sec = $r->sector ?? 'other';
            $desk = $sectorDesk[$sec] ?? $sec . '-desk';
            $economics = json_decode($r->economics ?? '[]', true) ?: [];
            $model = $economics['model'] ?? 'commission';
            $pipelines[] = [
                'id' => (int) $r->id,
                'code' => $r->code,
                'name' => ucwords(str_replace('-', ' ', $r->slug ?? $r->code)),
                'category' => $sec,
                'sector' => $sec,
                'phase' => (int) ($r->phase ?? 1),
                'status' => $r->status ?? 'partial',
                'owner_desk' => $desk,
                'external_systems' => [$sec . '-system'],
                'mechanism' => in_array($model, ['commission', 'flat_fee', 'subscription', 'listing', 'escrow']) ? $model : 'commission',
                'full_maturity_kes' => (int) ($economics['full_maturity_kes'] ?? 36000000),
                'take_rate' => $economics['take_rate'] ?? $model,
                'dependencies' => [],
            ];
        }

        file_put_contents("{$path}/pipelines.json", json_encode(['pipelines' => $pipelines], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // Only forward parent->child edges (parent id < child id) to keep a DAG.
        $edges = [];
        foreach ($all as $r) {
            if ($r->parent && $r->parent !== '' && isset($codeToId[$r->parent]) && $codeToId[$r->parent] < (int) $r->id) {
                $edges[] = ['from' => $codeToId[$r->parent], 'to' => (int) $r->id];
            }
        }
        file_put_contents("{$path}/integration-map.json", json_encode(['edges' => $edges], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info('Generated pipelines.json (' . count($pipelines) . ' pipelines) + integration-map.json (' . count($edges) . ' edges)');

        return self::SUCCESS;
    }
}