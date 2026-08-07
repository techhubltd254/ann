<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Subscription billing engine (blueprint §Payments): rolls billing cycles,
 * generates invoices (16% VAT), marks overdue. Runs daily 04:00.
 */
class RunBilling extends Command
{
    protected $signature = 'billing:run';
    protected $description = 'Roll subscription billing cycles and generate invoices';

    public function handle(): int
    {
        $now = now();
        $invoiced = 0;
        $expired = 0;

        $subs = DB::table('user_subscriptions')->where('status', 'active')->get();
        foreach ($subs as $sub) {
            // expire lapsed subscriptions
            if ($sub->ends_at && $now->greaterThan($sub->ends_at)) {
                DB::table('user_subscriptions')->where('id', $sub->id)->update(['status' => 'expired', 'updated_at' => $now]);
                $expired++;
                continue;
            }

            // open a new cycle when the current one ends
            $currentCycle = DB::table('billing_cycles')
                ->where('subscription_id', $sub->id)
                ->orderByDesc('cycle_number')
                ->first();

            $needsCycle = ! $currentCycle || $now->greaterThanOrEqualTo($currentCycle->period_end);
            if (! $needsCycle) {
                continue;
            }

            $plan = DB::table('subscription_plans')->where('id', $sub->subscription_plan_id)->first();
            if (! $plan) continue;

            $cycleNumber = $currentCycle ? $currentCycle->cycle_number + 1 : 1;
            $periodStart = $currentCycle ? $currentCycle->period_end : $sub->starts_at;
            $periodEnd = now()->parse($periodStart)->addMonth();

            $cycleId = DB::table('billing_cycles')->insertGetId([
                'subscription_id' => $sub->id,
                'cycle_number' => $cycleNumber,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'amount' => $plan->price,
                'currency' => $plan->currency ?? 'KES',
                'status' => 'open',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // invoice with 16% VAT
            $subtotal = (float) $plan->price;
            $tax = round($subtotal * 0.16, 2);
            $invoiceId = DB::table('invoices')->insertGetId([
                'invoice_number' => 'INV-' . strtoupper(Str::random(8)),
                'user_id' => $sub->user_id,
                'subscription_id' => $sub->id,
                'billing_cycle_id' => $cycleId,
                'type' => 'subscription',
                'status' => 'open',
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $subtotal + $tax,
                'currency' => $plan->currency ?? 'KES',
                'due_date' => now()->parse($periodStart)->addDays(7),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('invoice_items')->insert([
                'invoice_id' => $invoiceId,
                'description' => ($plan->name ?? 'Subscription') . " — cycle {$cycleNumber} (" . now()->parse($periodStart)->toDateString() . ' → ' . now()->parse($periodEnd)->toDateString() . ')',
                'quantity' => 1,
                'unit_price' => $subtotal,
                'tax_rate' => 16,
                'tax_amount' => $tax,
                'total' => $subtotal + $tax,
                'reference_type' => 'subscription_plan',
                'reference_id' => $plan->id,
                'created_at' => $now,
            ]);
            $invoiced++;
        }

        $this->info("billing: {$invoiced} invoices generated, {$expired} subscriptions expired");
        return self::SUCCESS;
    }
}
