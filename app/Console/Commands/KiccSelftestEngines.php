<?php

namespace App\Console\Commands;

use App\Kicc\Services\GLReportingService;
use App\Kicc\Services\MotherPoolService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * End-to-end engine self-test against the configured database (TiDB/local).
 * Run: php artisan kicc:selftest-engines            (adds SELFTEST rows)
 *      php artisan kicc:selftest-engines --cleanup  (removes them again)
 */
class KiccSelftestEngines extends Command
{
    protected $signature = 'kicc:selftest-engines {--cleanup}';
    protected $description = 'Self-test the General Ledger + Mother-Pool engines (hold/capture/split/refund, trial balance, idempotent settlement)';

    public function handle(): int
    {
        if ($this->option('cleanup')) return $this->cleanup();

        $ledger = app(\App\Kicc\Services\LedgerService::class);
        $reporting = app(GLReportingService::class);
        $pools = app(MotherPoolService::class);
        $ok = function (string $name, bool $cond) {
            $this->line(($cond ? 'PASS' : 'FAIL') . "  {$name}");
            if (!$cond) throw new \RuntimeException("Self-test failed at: {$name}");
        };

        // 1. ledger lifecycle: hold → capture → split
        $txId = $ledger->hold('SELFTEST', 1000.00, 'selftest', 0, null, null, ['tag' => 'selftest']);
        $ledger->capture($txId);
        $ledger->split($txId, [
            ['payee_type' => 'vendor', 'payee_id' => 1, 'split_code' => 'primary', 'amount' => 800.00],
            ['payee_type' => 'vendor', 'payee_id' => 2, 'split_code' => 'secondary', 'amount' => 200.00],
        ]);
        $tx = DB::table('ledger_transactions')->where('id', $txId)->first();
        $ok('hold → capture lifecycle', $tx->status === 'settled');
        $ok('splits recorded (2)', DB::table('ledger_splits')->where('ledger_transaction_id', $txId)->count() === 2);

        // 2. refund path
        $tx2 = $ledger->hold('SELFTEST', 500.00, 'selftest', 0, null, null, ['tag' => 'selftest']);
        $ledger->refund($tx2, 'selftest refund');
        $ok('hold → refund lifecycle', DB::table('ledger_transactions')->where('id', $tx2)->value('status') === 'refunded');

        // 3. double-entry invariant across the whole ledger
        $tb = $reporting->trialBalance();
        $ok('trial balance: total debits == total credits', $tb['balanced']);
        $ok('trial balance shows ' . count($tb['accounts']) . ' accounts', count($tb['accounts']) >= 2);

        // 4. mother-pool: open → contribute → dry-run → settle → idempotency
        $poolId = $pools->openPool('SELFTEST-POOL-' . uniqid(), now()->toDateString(), now()->toDateString());
        $pools->recordContribution($poolId, 'SELFTEST', 1000.00, 100.00, 1, 1);
        $pools->recordContribution($poolId, 'SELFTEST', 500.00, 50.00, 2, 1);
        // kicc_net = (100 + 50) * 0.895 after 10% holdback + 0.5% equalisation = 134.25
        $pool = DB::table('pools')->where('id', $poolId)->first();
        $ok('pool totals: balance == 134.25', abs((float) $pool->balance - 134.25) < 0.001);
        $ok('pool holdback == 15.00', abs((float) $pool->holdback_pct - 15.00) < 0.001);
        $ok('pool equalisation == 0.75', abs((float) $pool->equalisation_amount - 0.75) < 0.001);

        $dry = $pools->settlePool($poolId, true);
        $ok('dry-run does not settle', $dry['status'] === 'dry_run' && $pool->distribution_status !== 'settled');

        $res = $pools->settlePool($poolId, false);
        $ok('settlement succeeds', $res['status'] === 'settled');
        $distributed = (float) DB::table('pool_distributions')->where('mother_pool_id', $poolId)->sum('amount');
        $ok('exact-cent: distributed == balance', abs($distributed - (float) $pool->balance) < 0.001);

        $again = $pools->settlePool($poolId, false);
        $ok('idempotent: second settle is a no-op', $again['status'] === 'already_settled');
        $ok('idempotent: still 2 distribution rows', DB::table('pool_distributions')->where('mother_pool_id', $poolId)->count() === 2);

        // 5. period close
        $closed = $reporting->closePeriod(now()->toDateString(), now()->toDateString());
        $ok('period close snapshots balanced totals', $closed['balanced']);

        $this->info('ALL ENGINE SELF-TESTS PASSED');
        $this->line('cleanup: php artisan kicc:selftest-engines --cleanup');
        return self::SUCCESS;
    }

    private function cleanup(): int
    {
        $c = 0;
        foreach (DB::table('pools')->where('name', 'like', 'SELFTEST-POOL-%')->get() as $p) {
            $c += DB::table('pool_distributions')->where('mother_pool_id', $p->id)->delete();
            $c += DB::table('pool_contributions')->where('mother_pool_id', $p->id)->delete();
            $c += DB::table('pools')->where('id', $p->id)->delete();
        }
        foreach (DB::table('ledger_transactions')->where('pipeline_code', 'SELFTEST')->get() as $t) {
            $c += DB::table('ledger_splits')->where('ledger_transaction_id', $t->id)->delete();
            $c += DB::table('ledger_holds')->where('ledger_transaction_id', $t->id)->delete();
            $c += DB::table('gl_journal_lines')->whereIn('journal_entry_id',
                DB::table('gl_journal_entries')->where('reference_type', 'ledger_transaction')->where('reference_id', $t->id)->pluck('id'))->delete();
            $c += DB::table('gl_journal_entries')->where('reference_type', 'ledger_transaction')->where('reference_id', $t->id)->delete();
            $c += DB::table('ledger_transactions')->where('id', $t->id)->delete();
        }
        $this->info("cleaned {$c} SELFTEST rows");
        return self::SUCCESS;
    }
}
