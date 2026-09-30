<?php

namespace App\Console\Commands;

use App\Services\LedgerService;
use App\Services\PoolEngine;
use Illuminate\Console\Command;

class PoolCloseMonthly extends Command
{
    protected $signature = 'kicc:pool-close-monthly {--period= : YYYY-MM (default previous month)}';
    protected $description = 'Close a pool period: hold back 10%, equalise 0.5%, distribute the rest, post a ledger journal.';

    public function handle(): int
    {
        $period = $this->option('period') ?: now()->subMonth()->format('Y-m');

        $summary = PoolEngine::closePeriod($period);
        $distributable = (float) \DB::table('pool_periods')
            ->where(['period' => $period, 'scope' => 'global'])
            ->value('distributable');

        // Single balanced ledger journal summarizing the close.
        LedgerService::post([
            'journal_ref' => 'pool-close-' . $period,
            'memo'        => 'Monthly pool close',
            'source'      => 'scheduler',
            'entries' => [
                ['account' => 'platform_revenue',  'debit' => $distributable, 'credit' => 0.0],
                ['account' => 'pool_payable',      'debit' => 0.0, 'credit' => $distributable],
            ],
        ]);

        $this->info("Closed {$period}: {$summary['distributions']} distributions, distributable {$distributable}");
        return self::SUCCESS;
    }
}
