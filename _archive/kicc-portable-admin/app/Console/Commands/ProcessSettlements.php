<?php

namespace App\Console\Commands;

use App\Models\County;
use App\Models\PaymentIntent;
use App\Services\RevenueShareService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessSettlements extends Command
{
    protected $signature = 'settlements:process {--date= : Process settlements for a specific date (Y-m-d)}';
    protected $description = 'Process daily settlements — aggregate intents, split revenue, write batches';

    public function handle(RevenueShareService $revenueShare)
    {
        $date = $this->option('date') ?? now()->subDay()->format('Y-m-d');
        $this->info("Processing settlements for {$date}...");

        $intents = PaymentIntent::whereDate('confirmed_at', $date)
            ->where('status', 'confirmed')
            ->get();

        if ($intents->isEmpty()) {
            $this->info('No confirmed intents found for this date.');
            return 0;
        }

        $byCounty = $intents->groupBy(function($i) {
            return optional($i->user)->county_id ?? 0;
        });

        foreach ($byCounty as $countyId => $countyIntents) {
            $county = $countyId ? County::find($countyId) : null;
            $totalAmount = $countyIntents->sum('amount');

            $batch = DB::table('settlement_batches')->insertGetId([
                'county_id' => $countyId ?: null,
                'settlement_date' => $date,
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($countyIntents as $intent) {
                $referenceType = $intent->reference_type;
                $split = $revenueShare->split($intent->amount, $referenceType, $county);

                DB::table('settlement_transactions')->insert([
                    'batch_id' => $batch,
                    'payment_intent_id' => $intent->id,
                    'amount' => $intent->amount,
                    'platform_amount' => $split['platform'],
                    'county_amount' => $split['county'],
                    'revenue_source' => $referenceType,
                    'created_at' => now(),
                ]);
            }

            $countyName = $county?->name ?? 'Unknown';
            $this->line("  ✅ {$countyName}: {$countyIntents->count()} intents, KSh "
                . number_format($totalAmount, 0) . " total");
        }

        $this->info('Settlements processed successfully.');
        return 0;
    }
}
