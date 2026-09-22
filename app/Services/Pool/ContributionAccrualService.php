<?php

namespace App\Services\Pool;

use App\Models\Pool\PoolContribution;
use App\Models\Pool\Pool;
use Illuminate\Support\Facades\DB;

/**
 * ContributionAccrualService — wires every escrow release and settlement
 * batch into the mother pool. Called by EscrowService::releaseFunds()
 * and SettlementBatch::settle().
 */
class ContributionAccrualService
{
    public function accrue(
        string $sourceType, int $sourceId, float $grossAmount, float $platformFee,
        ?int $countyId, ?int $sectorId, ?int $entityId, ?string $entityType,
        ?int $sponsorId = null
    ): PoolContribution {
        $pool = Pool::firstOrCreate(
            ['scope' => 'global', 'scope_id' => null, 'name' => 'KICC Mother Pool'],
            ['holdback_pct' => config('kicc.pool.holdback_pct', 10.0), 'is_active' => true]
        );

        $poolShare = $platformFee;
        $period = now()->format('Y-m');

        $contribution = PoolContribution::create([
            'pool_id'      => $pool->id,
            'source_type'  => $sourceType,
            'source_id'    => $sourceId,
            'county_id'    => $countyId,
            'sector_id'    => $sectorId,
            'entity_type'  => $entityType,
            'entity_id'    => $entityId,
            'gross_amount' => $grossAmount,
            'platform_fee' => $platformFee,
            'pool_share'   => $poolShare,
            'period_id'    => $period,
        ]);

        $pool->increment('balance', $poolShare);

        // Sponsor referral chain
        if ($sponsorId && $entityId) {
            $sponsorShare = $poolShare * 0.02; // referral_pct tier2 = 2%
            DB::table('sponsor_referrals')->insert([
                'sponsor_id' => $sponsorId,
                'seller_id'  => $entityId,
                'pool_contribution_id' => $contribution->id,
                'amount'     => $sponsorShare,
                'status'     => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Bust cache so Mother Admin reflects new balance
        app(\App\Services\CacheSyncService::class)->kicc();

        return $contribution;
    }
}