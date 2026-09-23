<?php

namespace App\Kicc\Services;

use App\Kicc\Joiner\MotherPipelineJoiner;
use Illuminate\Support\Facades\DB;

/**
 * Executes a Mother-Pipeline Joiner plan against the real engines:
 *   - persists run + postings + reconciliations (idempotent via unique keys)
 *   - posts the unified journals through LedgerService (balanced-or-throw)
 *   - records every contribution into the Mother-Pool
 * A repeated batch_id is a NO-OP ('already_posted') — never double-posted.
 */
class JoinerService
{
    public function __construct(private LedgerService $ledger, private MotherPoolService $pools) {}

    /**
     * @param callable $gmvProvider function (string $code, array $entry): float
     */
    public function run(string $configDir, callable $gmvProvider, string $rateMode = 'conservative', bool $commit = false, ?int $poolId = null, ?string $sectorFilter = null): array
    {
        $joiner = MotherPipelineJoiner::fromConfig($configDir);
        $plan = $joiner->buildPlan($gmvProvider, $rateMode);

        if ($sectorFilter !== null) {
            $plan = $this->filterPlanBySector($plan, $sectorFilter);
        }
        $recon = $joiner->reconcile($plan);

        // idempotency guard: same batch posted twice is a no-op
        $existing = DB::table('joiner_runs')->where('batch_id', $plan['batch_id'])->first();
        if ($existing) {
            return ['status' => 'already_posted', 'batch_id' => $plan['batch_id'], 'run_id' => $existing->id, 'reconciliation' => $recon];
        }
        if (!$commit) {
            return ['status' => 'dry_run', 'batch_id' => $plan['batch_id'], 'reconciliation' => $recon,
                    'locked' => $plan['locked'], 'skipped' => $plan['skipped'],
                    'postings' => count($plan['postings']), 'contributions' => count($plan['contributions'])];
        }

        $runId = DB::table('joiner_runs')->insertGetId([
            'batch_id' => $plan['batch_id'],
            'mode' => 'committed',
            'sector_filter' => $sectorFilter,
            'totals' => json_encode($plan['totals']),
            'variance_report' => json_encode($recon['variance_report']),
            'status' => $recon['balanced'] ? 'balanced' : 'variance_detected',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        foreach ($plan['postings'] as $p) {
            DB::table('joiner_postings')->insertOrIgnore([
                'batch_id' => $plan['batch_id'],
                'posting_key' => $p['posting_key'],
                'pipeline_code' => $p['pipeline_code'],
                'sector' => $p['sector'],
                'parent' => $p['parent'],
                'gl_account' => $p['gl_account'],
                'debit' => $p['debit'],
                'credit' => $p['credit'],
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        foreach ($recon['variance_report'] as $v) {
            DB::table('joiner_reconciliations')->insert([
                'batch_id' => $plan['batch_id'], 'scope' => 'variance', 'reference' => $v,
                'expected' => 0, 'actual' => 0, 'variance' => 0, 'balanced' => false,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // unified journals into the real GL (LedgerService throws if unbalanced)
        $byPipeline = [];
        foreach ($plan['postings'] as $p) {
            $byPipeline[$p['pipeline_code']][] = $p;
        }
        foreach ($byPipeline as $code => $lines) {
            $this->ledger->post($runId, 'Joiner batch ' . $plan['batch_id'] . ' pipeline ' . $code,
                array_map(fn ($l) => ['code' => $l['gl_account'], 'debit' => (float) $l['debit'], 'credit' => (float) $l['credit']], $lines));
        }

        // every contribution into the Mother-Pool
        if ($poolId !== null) {
            foreach ($plan['contributions'] as $c) {
                $this->pools->recordContribution($poolId, $c['pipeline_code'], (float) $c['gmv'], (float) $c['fee_charged'], $c['county_id'], $c['sector_id'] ?? null);
            }
        }

        return ['status' => 'posted', 'batch_id' => $plan['batch_id'], 'run_id' => $runId,
                'reconciliation' => $recon, 'postings' => count($plan['postings']),
                'contributions' => count($plan['contributions'])];
    }

    private function filterPlanBySector(array $plan, string $sector): array
    {
        $keep = fn ($row) => ($row['sector'] ?? null) === $sector;
        $plan['postings'] = array_values(array_filter($plan['postings'], $keep));
        $plan['contributions'] = array_values(array_filter($plan['contributions'], $keep));
        $plan['locked'] = array_values(array_filter($plan['locked'], fn ($l) => ($this->sectorOf($l['code']) ?? null) === $sector));
        $plan['totals'] = [
            'gmv' => array_sum(array_column($plan['contributions'], 'gmv')),
            'fees' => array_sum(array_column($plan['contributions'], 'fee_charged')),
            'holdback' => array_sum(array_column($plan['contributions'], 'holdback')),
            'equalisation' => array_sum(array_column($plan['contributions'], 'equalisation')),
            'kicc_net' => array_sum(array_column($plan['contributions'], 'kicc_net')),
            'vendor_payout' => array_sum(array_column($plan['contributions'], 'vendor_payout')),
        ];
        foreach ($plan['totals'] as $k => $v) $plan['totals'][$k] = round((float) $v, 2);
        $plan['batch_id'] = 'JPB-' . date('Ymd') . '-' . substr(MotherPipelineJoiner::canonicalHash($plan['postings']), 0, 12) . '-' . $sector;
        return $plan;
    }

    private function sectorOf(string $code): ?string
    {
        static $map = null;
        if ($map === null) {
            $map = [];
            foreach ($this->registryCache() as $e) $map[$e['code']] = $e['sector'];
        }
        return $map[$code] ?? null;
    }

    private function registryCache(): array
    {
        return MotherPipelineJoiner::fromConfig(config_path())->registry();
    }
}
