<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Nightly vendor grading — the blueprint's two-score model (§Layer 6):
 *
 *  TRUST SCORE (0–100, earned, public grade A/B/C/D):
 *    verification status (gov ID / KRA / phone) + fulfilled-order rate +
 *    dispute rate + catalogue breadth + account age. Earned only.
 *
 *  VISIBILITY SCORE (purchasable boost): subscription tier + paid placements.
 *    ALWAYS labeled Sponsored and never outranks trust (fairness floor).
 */
class ScoreVendors extends Command
{
    protected $signature = 'vendors:score';
    protected $description = 'Compute Trust + Visibility scores for all vendors';

    public function handle(): int
    {
        $vendors = DB::table('users')->whereExists(function ($q) {
            $q->select(DB::raw(1))->from('products')->whereColumn('products.user_id', 'users.id');
        })->get(['id', 'email_verified_at', 'phone_verified_at', 'kra_pin', 'id_number', 'created_at', 'account_type']);

        $scored = 0;
        foreach ($vendors as $v) {
            $productCount = DB::table('products')->where('user_id', $v->id)->count();

            // Trust components
            $verification = 0;
            if ($v->email_verified_at) $verification += 10;
            if ($v->phone_verified_at) $verification += 10;
            if (! empty($v->kra_pin)) $verification += 15;
            if (! empty($v->id_number)) $verification += 5;   // up to 40

            $orders = DB::table('orders')->where('user_id', $v->id);
            $totalOrders = (clone $orders)->count();
            $fulfilled = (clone $orders)->where('status', 'delivered')->count();
            $fulfillmentScore = $totalOrders > 0 ? (int) round(30 * $fulfilled / $totalOrders) : 15; // neutral start

            $disputes = DB::table('dispute_cases')
                ->join('escrow_transactions', 'dispute_cases.escrow_transaction_id', '=', 'escrow_transactions.id')
                ->where('escrow_transactions.seller_id', $v->id)
                ->count();
            $disputePenalty = min(20, $disputes * 5);

            $breadth = min(10, $productCount);               // up to 10
            $ageDays = now()->diffInDays($v->created_at ?? now());
            $ageScore = (int) min(10, floor($ageDays / 30)); // up to 10 (10 months)

            $trust = max(0, min(100, $verification + $fulfillmentScore + $breadth + $ageScore - $disputePenalty));
            $grade = $trust >= 80 ? 'A' : ($trust >= 60 ? 'B' : ($trust >= 40 ? 'C' : 'D'));

            // Visibility (purchasable; from active subscription)
            $visibility = DB::table('user_subscriptions')
                ->where('user_id', $v->id)
                ->where('status', 'active')
                ->exists() ? 20 : 0;

            DB::table('users')->where('id', $v->id)->update([
                'trust_score' => $trust,
                'trust_grade' => $grade,
                'visibility_score' => $visibility,
                'updated_at' => now(),
            ]);
            $scored++;
        }

        $this->info("vendors scored: {$scored}");
        return self::SUCCESS;
    }
}
