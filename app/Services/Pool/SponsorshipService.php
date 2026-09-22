<?php

namespace App\Services\Pool;

/**
 * Sponsorship referral fee engine — Algorithm 14 from kicc-algorithms.
 * Tracks the "sponsored by" graph edges and computes referral fees.
 */
class SponsorshipService
{
    public function referralFee(int $sponsorTier, float $transactionFee): float
    {
        $pcts = config('kicc.pool.sponsorship_referral_pct', [1 => 0.01, 2 => 0.02]);
        $pct = $pcts[$sponsorTier] ?? 0.01;
        return round($transactionFee * $pct, 2);
    }
}