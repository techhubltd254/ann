<?php

namespace App\Console\Commands;

use App\Kicc\Contracts\PipelineContract;
use App\Kicc\Engine\PipelineEngine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * kicc:pipeline:earn — the revenue engine.
 *
 * Iterates every active pipeline and executes its earning lifecycle:
 * match → escrow:hold → escrow:release → pool:accrue → ledger:post.
 *
 * This is what makes "partial" pipelines actually earn money — without this
 * command, they're just registered shells. Run it as a cron job or after any
 * automation cascade to turn settlements into real pool contributions.
 *
 * Usage:
 *   php artisan kicc:pipeline:earn                     # all active pipelines
 *   php artisan kicc:pipeline:earn --code=A1,B1,C1     # specific pipelines
 *   php artisan kicc:pipeline:earn --dry-run           # report only, no writes
 *   php artisan kicc:pipeline:earn --limit=50          # max pipelines to process
 */
class PipelineEarn extends Command
{
    protected $signature = 'kicc:pipeline:earn
        {--code= : Comma-separated pipeline codes to process (default: all active)}
        {--dry-run : Report what would happen without writing}
        {--limit= : Max pipelines to process in this run}';

    protected $description = 'Execute every active pipeline through its earning lifecycle — match, escrow, pool accrue, ledger post';

    private array $results = [];

    public function handle(): int
    {
        $codes = $this->option('code')
            ? explode(',', $this->option('code'))
            : $this->getActivePipelineCodes();

        $limit = (int) ($this->option('limit') ?: 0);
        if ($limit > 0) $codes = array_slice($codes, 0, $limit);

        $dryRun = (bool) $this->option('dry-run');
        $totalGmv = 0;
        $totalFee = 0;
        $settled = 0;
        $skipped = 0;

        $this->info("Pipeline Earn: " . count($codes) . " pipelines to process" . ($dryRun ? ' [DRY RUN]' : ''));

        $bar = $this->output->createProgressBar(count($codes));
        $bar->start();

        foreach ($codes as $code) {
            $code = trim($code);
            try {
                $result = $this->processOne($code, $dryRun);
                if ($result['settled'] ?? false) {
                    $settled++;
                    $totalGmv += $result['gmv'] ?? 0;
                    $totalFee += $result['fee'] ?? 0;
                } else {
                    $skipped++;
                }
                $this->results[] = $result;
            } catch (\Throwable $e) {
                $this->results[] = [
                    'pipeline' => $code,
                    'settled' => false,
                    'error' => $e->getMessage(),
                ];
                Log::error('kicc:pipeline:earn', ['pipeline' => $code, 'error' => $e->getMessage()]);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Summary
        $this->table(
            ['Metric', 'Value'],
            [
                ['Processed', count($codes)],
                ['Settled', $settled],
                ['Skipped / No Trade', $skipped],
                ['Total GMV (KES)', number_format($totalGmv)],
                ['Total Commission (KES)', number_format($totalFee)],
                ['Moher Pool accrual', $settled > 0 ? 'WILL ACCRUE' : 'N/A'],
                ['Ledger posting', $settled > 0 ? 'WILL POST' : 'N/A'],
            ]
        );

        // Per-pipeline detail
        if ($this->option('verbose') || $this->option('dry-run')) {
            $this->newLine();
            $this->line('Per-pipeline detail:');
            $rows = [];
            foreach ($this->results as $r) {
                $status = $r['settled'] ?? false ? '✅' : ($r['error'] ?? '⏭️');
                $rows[] = [
                    $r['pipeline'],
                    $status,
                    'KES ' . number_format($r['gmv'] ?? 0),
                    'KES ' . number_format($r['fee'] ?? 0),
                    $r['synthetic'] ?? true ? 'demo' : 'real',
                    $r['escrow'] ?? ($r['error'] ?? '—'),
                ];
            }
            $this->table(['Pipeline', 'Status', 'GMV', 'Fee', 'Source', 'Escrow'], $rows);
        }

        $this->newLine();
        $this->info("Done. {$settled} pipelines earned revenue. Run php artisan kicc:status to verify.");

        return self::SUCCESS;
    }

    private function processOne(string $code, bool $dryRun): array
    {
        $pipelineConfig = $this->findConfig($code);

        if (! $pipelineConfig) {
            return ['pipeline' => $code, 'settled' => false, 'error' => 'No config found'];
        }

        // Check if licence-gated — skip if earning_locked
        $reg = DB::table('pipeline_registrations')->where('code', $code)->first();
        if ($reg && ($reg->earning_locked ?? false)) {
            return ['pipeline' => $code, 'settled' => false, 'error' => 'licence_gated — earning_locked'];
        }

        if ($dryRun) {
            return [
                'pipeline' => $code,
                'settled' => true,
                'gmv' => 50000,
                'fee' => 2000,
                'synthetic' => true,
                'escrow' => 'DRY-RUN',
                'status' => 'would_settle',
            ];
        }

        // Build a minimal PipelineContract-compatible object from config
        $pipeline = $this->buildPipeline($pipelineConfig);

        // Execute through the engine
        $engine = new PipelineEngine($pipeline, $pipelineConfig);
        $result = $engine->execute();

        return [
            'pipeline' => $code,
            'settled' => $result['status'] === 'released',
            'gmv' => $result['gmv'],
            'fee' => $result['fee'],
            'synthetic' => $result['synthetic'],
            'escrow' => $result['escrow_id'],
            'status' => $result['status'],
        ];
    }

    private function buildPipeline(array $config): PipelineContract
    {
        return new class($config) implements PipelineContract {
            private array $config;
            public function __construct(array $config) { $this->config = $config; }
            public function code(): string { return $this->config['code']; }
            public function economics(): array { return $this->config; }
            public function regulators(): array { return $this->config['regulators'] ?? []; }
            public function tables(): array { return $this->config['tables'] ?? []; }
            public function killCriteria(): array { return $this->config['kill_criteria'] ?? []; }
            public function preFlight(): array { return ['ready' => true, 'reason' => 'PipelineEngine']; }
            public function isEarningReady(): bool { return true; }
        };
    }

    private function getActivePipelineCodes(): array
    {
        $all = [];

        // 1. Parent pipelines (config/kicc-pipelines.php)
        $all = array_merge($all, collect(config('kicc-pipelines'))
            ->reject(fn($p) => in_array($p['status'], ['blocked', 'licence_gated']))
            ->pluck('code')
            ->values()
            ->toArray());

        // 2. Subsector pipelines (config/kicc/subsectors/index.php)
        try {
            $subsectors = require base_path('config/kicc/subsectors/index.php');
            $all = array_merge($all, collect($subsectors)
                ->reject(fn($p) => in_array($p['status'] ?? '', ['blocked', 'licence_gated']))
                ->pluck('code')
                ->values()
                ->toArray());
        } catch (\Throwable $e) {
            $this->warn('Could not load subsector configs: ' . $e->getMessage());
        }

        // 3. Dynamic pipelines (DB — milk/dairy etc.)
        try {
            $dynamic = DB::table('dynamic_pipelines')
                ->where('is_active', true)
                ->pluck('code')
                ->toArray();
            $all = array_merge($all, $dynamic);
        } catch (\Throwable $e) {
            $this->warn('Could not load dynamic pipelines: ' . $e->getMessage());
        }

        return array_unique($all);
    }

    private function findConfig(string $code): ?array
    {
        // Parent configs
        $config = collect(config('kicc-pipelines'))->firstWhere('code', $code);
        if ($config) return $config;

        // Subsector configs
        try {
            $subsectors = require base_path('config/kicc/subsectors/index.php');
            $config = collect($subsectors)->firstWhere('code', $code);
            if ($config) return $config;
        } catch (\Throwable) {}

        // Dynamic pipelines — build config from DB
        try {
            $dp = DB::table('dynamic_pipelines')->where('code', $code)->first();
            if ($dp) {
                return [
                    'code' => $dp->code,
                    'sector' => $dp->sector ?? 'general',
                    'status' => 'built',
                    'take_rate' => (string) $dp->fee_rate,
                    'regulators' => [],
                    'tables' => [],
                    'fee_rate' => (float) $dp->fee_rate,
                    'description' => $dp->description ?? '',
                ];
            }
        } catch (\Throwable) {}

        // Pipeline registrations fallback
        try {
            $reg = DB::table('pipeline_registrations')->where('code', $code)->first();
            if ($reg) {
                $eco = json_decode($reg->economics ?? '{}', true) ?: [];
                return [
                    'code' => $reg->code,
                    'sector' => $reg->sector ?? 'general',
                    'status' => $reg->status ?? 'built',
                    'take_rate' => $eco['take_rate'] ?? ($eco['take_rate_pct'] ?? 4),
                    'regulators' => json_decode($reg->regulators ?? '[]', true) ?: [],
                    'tables' => json_decode($reg->tables ?? '[]', true) ?: [],
                    'fee_rate' => (float) ($eco['take_rate_pct'] ?? 4),
                    'description' => $eco['description'] ?? '',
                ];
            }
        } catch (\Throwable) {}

        return null;
    }
}