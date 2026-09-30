<?php

namespace App\Services;

use App\Models\EscrowTransaction;
use App\Models\Marketplace\Order;
use Illuminate\Support\Facades\DB;

/**
 * Mother-admin pool distribution engine.
 *   weight_i = contribution_i^alpha * quality_i^beta
 *   payout_i = distributable * weight_i / sum(weights)
 *
 * Defaults: alpha=0.7, beta=0.3, holdback=10%, equalisation=0.5%.
 */
class PoolEngine
{
    public const DEFAULT_ALPHA = 0.7;
    public const DEFAULT_BETA  = 0.3;
    public const HOLDBACK_PCT  = 0.10;
    public const EQUALISATION_PCT = 0.005;

    /**
     * Recalc pool for a period. Idempotent: clears pool_distributions for that
     * period first, then re-inserts the current state.
     */
    public static function recalcFor(string $period, string $scope = 'global', string $reason = 'manual'): int
    {
        return DB::transaction(function () use ($period, $scope, $reason) {
            DB::table('pool_periods')->updateOrInsert(
                ['period' => $period, 'scope' => $scope],
                ['status' => 'calculating', 'alpha' => self::DEFAULT_ALPHA, 'beta' => self::DEFAULT_BETA, 'updated_at' => now(), 'created_at' => now()]
            );

            // Aggregate contributions per county.
            $contributions = DB::table('orders')
                ->join('users as seller', 'orders.user_id', '=', 'seller.id')
                ->whereNotNull('seller.county_id')
                ->whereYear('orders.placed_at', substr($period, 0, 4))
                ->whereMonth('orders.placed_at', substr($period, 5, 2))
                ->where('orders.status', 'paid')
                ->where('orders.placed_at', '<=', now())
                ->groupBy('seller.county_id')
                ->selectRaw('seller.county_id as county_id, SUM(orders.grand_total) as contribution')
                ->pluck('contribution', 'county_id');

            $total = (float) $contributions->sum();
            if ($total <= 0) {
                DB::table('pool_periods')->where(['period' => $period, 'scope' => $scope])->update([
                    'status' => 'empty', 'updated_at' => now(),
                ]);
                return 0;
            }

            $holdback      = $total * self::HOLDBACK_PCT;
            $equalisation  = $total * self::EQUALISATION_PCT;
            $distributable = $total - $holdback - $equalisation;

            // Weights
            $alpha = self::DEFAULT_ALPHA;
            $beta  = self::DEFAULT_BETA;
            $weights = [];
            $sumWeights = 0.0;
            foreach ($contributions as $cid => $c) {
                $quality = (float) (DB::table('quality_scores')
                    ->where('subject_type', 'county')
                    ->where('subject_id', $cid)
                    ->value('score') ?? 50); // neutral default
                $w = pow(max((float) $c, 1.0), $alpha) * pow(max($quality, 1.0), $beta);
                $weights[$cid] = $w;
                $sumWeights += $w;
            }

            DB::table('pool_distributions')->where(['period' => $period, 'scope' => $scope])->delete();
            $rows = [];
            foreach ($weights as $cid => $w) {
                $rows[] = [
                    'period'        => $period,
                    'scope'         => $scope,
                    'county_id'     => $cid,
                    'contribution'  => (float) $contributions[$cid],
                    'weight'        => (float) $w,
                    'payout'        => $sumWeights > 0 ? ($distributable * ($w / $sumWeights)) : 0.0,
                    'breakdown'     => json_encode([
                        'alpha' => $alpha, 'beta' => $beta,
                        'quality' => (float) (DB::table('quality_scores')
                            ->where('subject_type', 'county')->where('subject_id', $cid)->value('score') ?? 50),
                        'reason' => $reason,
                    ]),
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ];
            }
            if (!empty($rows)) DB::table('pool_distributions')->insert($rows);

            DB::table('pool_periods')->where(['period' => $period, 'scope' => $scope])->update([
                'status'        => 'finalized',
                'gross_total'   => $total,
                'holdback'      => $holdback,
                'equalisation'  => $equalisation,
                'distributable' => $distributable,
                'closed_at'     => now(),
                'updated_at'    => now(),
            ]);

            return count($rows);
        });
    }

    /** Close a period and emit settlement rows. */
    public static function closePeriod(string $period): array
    {
        $count = self::recalcFor($period, 'global', 'monthly_close');
        return ['distributions' => $count, 'period' => $period];
    }
}
