<?php

namespace App\Kicc\Support;

/**
 * Largest-remainder cent allocation: split a total into exact-cent amounts
 * across weighted beneficiaries so the parts ALWAYS sum to the total.
 * Deterministic (same input → same output) — the property that makes
 * Mother-Pool settlement idempotent.
 */
class RemainderAllocator
{
    /**
     * @param float $total   exact amount to distribute (e.g. pool total_collected)
     * @param array  $weights key => basis amount (contribution × quality)
     * @return float[]        key => allocated amount; array_sum === $total
     */
    public static function allocate(float $total, array $weights): array
    {
        $sum = array_sum($weights);
        if ($sum <= 0 || $total <= 0) {
            return array_fill_keys(array_keys($weights), 0.0);
        }
        $raw = [];
        $floors = [];
        foreach ($weights as $key => $basis) {
            $exact = $total * ($basis / $sum);
            $raw[$key] = $exact;
            $floors[$key] = floor($exact * 100) / 100; // floor to cents
        }
        // cents still unassigned
        $remainingCents = (int) round(($total - array_sum($floors)) * 100);
        if ($remainingCents > 0) {
        // order by largest fractional remainder, ties broken by key for determinism
        $order = array_keys($weights);
            usort($order, function ($a, $b) use ($raw) {
                $diff = fmod($raw[$b] * 100, 1) <=> fmod($raw[$a] * 100, 1);
                if (abs($diff) < 1e-9) return strcmp($a, $b);
                return (int) $diff;
            });
            foreach (array_slice($order, 0, $remainingCents) as $key) {
                $floors[$key] += 0.01;
            }
        }
        return array_map(fn ($v) => round($v, 2), $floors);
    }
}
