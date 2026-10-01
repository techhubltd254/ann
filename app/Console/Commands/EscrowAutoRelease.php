<?php

namespace App\Console\Commands;

use App\Models\EscrowTransaction;
use App\Services\AuditLogger;
use App\Services\JournalService;
use App\Events\GenericDomainEvent;
use App\Services\PoolEngine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EscrowAutoRelease extends Command
{
    protected $signature = 'kicc:escrow-auto-release {--dry-run : print what would happen}';
    protected $description = 'Release escrow transactions whose delivery was confirmed 7+ days ago.';

    public function handle(): int
    {
        $cutoff = now()->subDays(7);
        $dryRun = (bool) $this->option('dry-run');

        $rows = EscrowTransaction::where('status', 'held')
            ->whereNotNull('delivery_confirmed_at')
            ->where('delivery_confirmed_at', '<=', $cutoff)
            ->limit(500)
            ->get();

        $released = 0;
        foreach ($rows as $escrow) {
            if ($dryRun) {
                $this->line("dry-run: would release {$escrow->escrow_id} amount {$escrow->amount}");
                continue;
            }

            DB::transaction(function () use ($escrow, &$released) {
                $steps = collect($escrow->steps ?? [])->map(fn ($s) => array_merge($s, ['done' => true]))->values()->all();
                $escrow->update([
                    'status' => 'released', 'steps' => $steps, 'current_step' => 4,
                    'released_at' => now(), 'released_by' => null,
                ]);

                JournalService::post([
                    'journal_ref' => 'escrow-auto-' . $escrow->escrow_id,
                    'memo'        => 'Auto-release after 7-day delivery window',
                    'source'      => 'scheduler',
                    'entries' => [
                        ['account' => 'escrow_liability', 'debit' => (float) $escrow->amount, 'credit' => 0.0],
                        ['account' => 'seller_payable',   'debit' => 0.0, 'credit' => (float) $escrow->amount],
                    ],
                ]);

                AuditLogger::log(null, 'escrow.auto_released', EscrowTransaction::class, $escrow->id, [
                    'amount' => (float) $escrow->amount,
                ]);
                $released++;
            });
        }

        PoolEngine::recalcFor(period: now()->format('Y-m'), scope: 'global', reason: 'scheduled_release');
        $this->info("Released {$released} escrow(s).");
        return self::SUCCESS;
    }
}
