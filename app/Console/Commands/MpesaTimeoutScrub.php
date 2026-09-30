<?php

namespace App\Console\Commands;

use App\Models\Payment\PaymentIntent;
use App\Services\AuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MpesaTimeoutScrub extends Command
{
    protected $signature = 'kicc:mpesa-timeout-scrub {--minutes=30 : expire pending STK pushes older than this}';
    protected $description = 'Cancel M-Pesa STK push payment intents that never received a callback.';

    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');
        $cutoff = now()->subMinutes($minutes);

        $rows = PaymentIntent::where('status', 'pending')
            ->where('created_at', '<=', $cutoff)
            ->limit(500)->get();

        $cancelled = 0;
        foreach ($rows as $intent) {
            DB::transaction(function () use ($intent, &$cancelled) {
                $intent->update(['status' => 'timed_out', 'cancelled_at' => now()]);
                AuditLogger::log(null, 'payment.timed_out', PaymentIntent::class, $intent->id, [
                    'amount' => (float) $intent->amount,
                    'minutes' => $minutes,
                ]);
                $cancelled++;
            });
        }
        $this->info("Scrubbed {$cancelled} payment intent(s).");
        return self::SUCCESS;
    }
}
