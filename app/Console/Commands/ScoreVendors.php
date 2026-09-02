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
            if ($v->email_verified_at) $verification += (int) config('kicc.vendor_scoring.verification_email', 10);
            if ($v->phone_verified_at) $verification += (int) config('kicc.vendor_scoring.verification_phone', 10);
            if (! empty($v->kra_pin)) $verification += (int) config('kicc.vendor_scoring.verification_kra_pin', 15);
            if (! empty($v->id_number)) $verification += (int) config('kicc.vendor_scoring.verification_id_number', 5);

            $orders = DB::table('orders')->where('user_id', $v->id);
            $totalOrders = (clone $orders)->count();
            $fulfilled = (clone $orders)->where('status', 'delivered')->count();
            $fulfillmentMax = (int) config('kicc.vendor_scoring.fulfillment_max', 30);
            $fulfillmentNeutral = (int) config('kicc.vendor_scoring.fulfillment_neutral', 15);
            $fulfillmentScore = $totalOrders > 0 ? (int) round($fulfillmentMax * $fulfilled / $totalOrders) : $fulfillmentNeutral;

            $disputes = DB::table('dispute_cases')
                ->join('escrow_transactions', 'dispute_cases.escrow_transaction_id', '=', 'escrow_transactions.id')
                ->where('escrow_transactions.seller_id', $v->id)
                ->count();
            $disputePenalty = min((int) config('kicc.vendor_scoring.dispute_penalty_max', 20), $disputes * (int) config('kicc.vendor_scoring.dispute_penalty_per', 5));

            $breadthCap = (int) config('kicc.vendor_scoring.breadth_cap', 10);
            $breadth = min($breadthCap, $productCount);
            $ageDays = now()->diffInDays($v->created_at ?? now());
            $agePerMonth = (int) config('kicc.vendor_scoring.age_score_per_month', 1);
            $ageMax = (int) config('kicc.vendor_scoring.age_score_max', 10);
            $ageScore = (int) min($ageMax, floor($ageDays / 30 * $agePerMonth));

            $trust = max(0, min(100, $verification + $fulfillmentScore + $breadth + $ageScore - $disputePenalty));
            $gradeA = (int) config('kicc.vendor_scoring.grade_a', 80);
            $gradeB = (int) config('kicc.vendor_scoring.grade_b', 60);
            $gradeC = (int) config('kicc.vendor_scoring.grade_c', 40);
            $grade = $trust >= $gradeA ? 'A' : ($trust >= $gradeB ? 'B' : ($trust >= $gradeC ? 'C' : 'D'));

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
