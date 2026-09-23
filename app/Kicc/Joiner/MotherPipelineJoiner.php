<?php

namespace App\Kicc\Joiner;

use App\Kicc\Support\Economics;
use App\Kicc\Support\TrialBalance;

/**
 * Mother-Pipeline Joiner engine (pure core — no framework dependencies).
 *
 * Orchestrates ALL subsector pipelines into ONE deterministic posting plan:
 *   - unified double-entry GL postings (balanced per pipeline AND per account)
 *   - Mother-Pool contributions (holdback 10% + equalisation 0.5% + kicc_net)
 *   - reconciliation with an explicit variance report
 *   - deterministic idempotency keys (same inputs -> same batch/posting keys)
 *
 * Consumes the exact config contracts already wired to the engines:
 *   config/kicc-pipelines.php, config/kicc/subsectors/index.php,
 *   config/kicc-engine-bindings.php
 */
class MotherPipelineJoiner
{
    /** @var array[] deterministic ordered subsector registry */
    private array $registry;
    private array $lockedStatuses;

    public function __construct(array $parents, array $subsectors, array $bindings)
    {
        $this->lockedStatuses = $bindings['earning_locked_statuses'] ?? ['licence_gated', 'blocked'];

        $indexed = [];
        foreach ($subsectors as $e) {
            foreach (['code', 'parent', 'sector', 'status', 'economics', 'engine'] as $k) {
                if (!array_key_exists($k, $e)) {
                    throw new \RuntimeException('Joiner registry error: subsector entry missing key "' . $k . '"');
                }
            }
            $indexed[$e['code']] = $e;
        }
        // deterministic run order: sector, parent, code (ascending)
        uasort($indexed, function ($a, $b) {
            return [$a['sector'], $a['parent'], $a['code']] <=> [$b['sector'], $b['parent'], $b['code']];
        });
        $this->registry = $indexed;

        // integrity: every subsector must reference a parent in the SAME sector
        $parentBySector = [];
        foreach ($parents as $p) {
            $parentBySector[$p['sector']][$p['code']] = true;
        }
        foreach ($this->registry as $e) {
            if (!isset($parentBySector[$e['sector']][$e['parent']])) {
                throw new \RuntimeException('Joiner registry error: subsector ' . $e['code']
                    . ' references unknown parent ' . $e['parent'] . ' in sector ' . $e['sector']);
            }
        }
    }

    public static function fromConfig(string $configDir): self
    {
        $dir = rtrim($configDir, '/');
        return new self(
            require $dir . '/kicc-pipelines.php',
            require $dir . '/kicc/subsectors/index.php',
            require $dir . '/kicc-engine-bindings.php'
        );
    }

    public function registry(): array { return $this->registry; }

    public function count(): int { return count($this->registry); }

    public function isLocked(array $entry): bool
    {
        return in_array($entry['status'], $this->lockedStatuses, true)
            || !empty($entry['engine']['ledger']['earning_locked']);
    }

    /** Deterministic fee for an entry under the selected rate mode. */
    public static function feeFor(array $entry, float $gmv, string $rateMode = 'conservative'): float
    {
        $econ = $entry['economics'];
        switch ($econ['model'] ?? '') {
            case 'commission':
                $pct = $econ['take_rate_pct'] ?? [0, 0];
                $rate = ($rateMode === 'optimistic') ? (float) $pct[1] : (float) $pct[0];
                return round($gmv * $rate / 100, 2);
            case 'flat_fee':
                return round((float) ($econ['flat_fee_kes'] ?? 0), 2);
            case 'rate_note':
            default:
                return 0.0; // negotiated per transaction — planned at zero
        }
    }

    /**
     * Build the unified posting plan over the whole registry.
     *
     * @param callable $gmvProvider function (string $code, array $entry): float
     * @param string   $rateMode    'conservative' (lower take-rate bound) | 'optimistic' (upper)
     */
    public function buildPlan(callable $gmvProvider, string $rateMode = 'conservative'): array
    {
        $postings = [];
        $contributions = [];
        $locked = [];
        $skipped = [];
        $order = [];
        $totals = ['gmv' => 0.0, 'fees' => 0.0, 'holdback' => 0.0, 'equalisation' => 0.0, 'kicc_net' => 0.0, 'vendor_payout' => 0.0];

        foreach ($this->registry as $entry) {
            $code = $entry['code'];
            $order[] = $code;

            if ($this->isLocked($entry)) {
                $locked[] = ['code' => $code, 'status' => $entry['status'],
                             'reason' => 'earning_locked — regulator gate not cleared'];
                continue;
            }

            $gmv = round((float) $gmvProvider($code, $entry), 2);
            if ($gmv <= 0) {
                $skipped[] = ['code' => $code, 'reason' => 'no_gmv_in_scenario'];
                continue;
            }

            $fee = self::feeFor($entry, $gmv, $rateMode);
            // quality multiplier defaults to 1.0 at plan level (dry-run governance rule);
            // the live multiplier is applied by QualityScoreService at execution time.
            $contribution = Economics::contribution(0, $code, $gmv, $fee, null, null, 1.0);
            $contributions[] = $contribution + ['sector' => $entry['sector'], 'parent' => $entry['parent']];

            // balanced double-entry pair per pipeline (unified plan)
            foreach ([
                ['gl_account' => 'escrow_clearing', 'debit' => $gmv, 'credit' => 0.0],
                ['gl_account' => 'merchant_payable', 'debit' => 0.0, 'credit' => $gmv],
                ['gl_account' => 'merchant_payable', 'debit' => $fee, 'credit' => 0.0],
                ['gl_account' => 'fees_income', 'debit' => 0.0, 'credit' => $fee],
            ] as $line) {
                $postings[] = $line + ['pipeline_code' => $code, 'sector' => $entry['sector'], 'parent' => $entry['parent']];
            }

            $totals['gmv'] += $gmv;
            $totals['fees'] += $fee;
            $totals['holdback'] += $contribution['holdback'];
            $totals['equalisation'] += $contribution['equalisation'];
            $totals['kicc_net'] += $contribution['kicc_net'];
            $totals['vendor_payout'] += $contribution['vendor_payout'];
        }

        foreach ($totals as $k => $v) {
            $totals[$k] = round($v, 2);
        }

        // deterministic idempotency keys
        $batchId = 'JPB-' . date('Ymd') . '-' . substr(self::canonicalHash($postings), 0, 12);
        $seq = 0;
        foreach ($postings as &$p) {
            $p['posting_key'] = $batchId . ':' . $p['pipeline_code'] . ':' . (++$seq);
        }
        unset($p);

        return [
            'batch_id' => $batchId,
            'deterministic_order' => $order,
            'locked' => $locked,
            'skipped' => $skipped,
            'postings' => $postings,
            'contributions' => $contributions,
            'totals' => $totals,
            'rate_mode' => $rateMode,
        ];
    }

    /**
     * Reconcile a plan. Balanced means EVERY check passed:
     *   1. per-pipeline debits == credits
     *   2. unified per-account debits == credits (whole-plan trial balance)
     *   3. economic identities per contribution (fee split, gmv split)
     *   4. plan-level totals tie-out
     * Any failure lands in 'variance_report' (human-readable) — never silently ignored.
     */
    public function reconcile(array $plan): array
    {
        $varianceReport = [];

        // 1. per-pipeline balance
        $perPipe = [];
        foreach ($plan['postings'] as $p) {
            $perPipe[$p['pipeline_code']] ??= ['debit' => 0.0, 'credit' => 0.0];
            $perPipe[$p['pipeline_code']]['debit'] += (float) $p['debit'];
            $perPipe[$p['pipeline_code']]['credit'] += (float) $p['credit'];
        }
        $pipelineVariances = [];
        foreach ($perPipe as $code => $t) {
            $diff = round($t['debit'] - $t['credit'], 2);
            if (abs($diff) > 0.001) {
                $pipelineVariances[$code] = $diff;
                $varianceReport[] = sprintf('PIPELINE UNBALANCED %s: debits %.2f vs credits %.2f (variance %.2f)', $code, $t['debit'], $t['credit'], $diff);
            }
        }

        // 2. unified per-account balance
        $tb = TrialBalance::rollup(array_map(fn ($p) => [
            'code' => $p['gl_account'], 'debit' => (float) $p['debit'], 'credit' => (float) $p['credit'],
        ], $plan['postings']));
        if (!$tb['balanced']) {
            $varianceReport[] = sprintf('UNIFIED LEDGER UNBALANCED: debits %.2f vs credits %.2f', $tb['total_debits'], $tb['total_credits']);
        }

        // 3. economic identities per contribution
        $identityVariances = [];
        foreach ($plan['contributions'] as $c) {
            $weightedFee = round((float) $c['fee_charged'] * (float) $c['quality_multiplier'], 2);
            if (abs(($c['holdback'] + $c['equalisation'] + $c['kicc_net']) - $weightedFee) > 0.001) {
                $identityVariances[] = ['code' => $c['pipeline_code'], 'check' => 'fee_split'];
                $varianceReport[] = sprintf('FEE SPLIT MISMATCH %s: holdback+equalisation+kicc_net != weighted fee', $c['pipeline_code']);
            }
            if (abs(($c['vendor_payout'] + $c['fee_charged']) - $c['gmv']) > 0.001) {
                $identityVariances[] = ['code' => $c['pipeline_code'], 'check' => 'gmv_split'];
                $varianceReport[] = sprintf('GMV SPLIT MISMATCH %s: vendor_payout+fee != gmv', $c['pipeline_code']);
            }
        }

        // 4. totals tie-out
        $t = $plan['totals'];
        if (abs(($t['kicc_net'] + $t['holdback'] + $t['equalisation']) - $t['fees']) > 0.01) {
            $varianceReport[] = 'TOTALS: kicc_net + holdback + equalisation != fees';
        }
        if (abs(($t['vendor_payout'] + $t['fees']) - $t['gmv']) > 0.01) {
            $varianceReport[] = 'TOTALS: vendor_payout + fees != gmv';
        }

        return [
            'balanced' => empty($varianceReport),
            'accounts' => $tb,
            'pipeline_variances' => $pipelineVariances,
            'identity_variances' => $identityVariances,
            'variance_report' => $varianceReport,
            'totals' => $t,
        ];
    }

    /** Deterministic canonical hash of the posting set (idempotency anchor). */
    public static function canonicalHash(array $postings): string
    {
        $canon = array_map(function ($p) {
            $p = array_intersect_key($p, array_flip(['pipeline_code', 'gl_account', 'debit', 'credit']));
            ksort($p);
            return $p;
        }, $postings);
        usort($canon, fn ($a, $b) => [$a['pipeline_code'], $a['gl_account'], $a['debit'], $a['credit']]
                                    <=> [$b['pipeline_code'], $b['gl_account'], $b['debit'], $b['credit']]);
        return sha1(json_encode($canon));
    }
}
