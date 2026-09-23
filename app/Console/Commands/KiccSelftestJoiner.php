<?php

namespace App\Console\Commands;

use App\Kicc\Services\GLReportingService;
use App\Kicc\Services\JoinerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Full end-to-end joiner self-test on the configured database.
 * Run: php artisan kicc:selftest-joiner            (adds SELFTEST-JOINER rows)
 *      php artisan kicc:selftest-joiner --cleanup  (removes them again)
 */
class KiccSelftestJoiner extends Command
{
    protected $signature = 'kicc:selftest-joiner {--cleanup}';
    protected $description = 'End-to-end Mother-Pipeline Joiner self-test (registry -> plan -> unified GL -> Mother-Pool -> idempotency)';

    public function handle(): int
    {
        if ($this->option('cleanup')) return $this->cleanup();

        $ok = function (string $name, bool $cond) {
            $this->line(($cond ? 'PASS' : 'FAIL') . "  {$name}");
            if (!$cond) throw new \RuntimeException("Joiner self-test failed at: {$name}");
        };

        $joiner = app(JoinerService::class);
        $reporting = app(GLReportingService::class);
        $poolId = DB::table('pools')->insertGetId([
            'name' => 'SELFTEST-JOINER-' . uniqid(),
            'period_start' => now()->toDateString(), 'period_end' => now()->toDateString(),
            'distribution_status' => 'open', 'created_at' => now(), 'updated_at' => now(),
        ]);

        // 1. dry-run over the WHOLE registry (no persistence)
        $dry = $joiner->run(config_path(), fn () => 100000.00, 'conservative', false);
        $ok('dry-run over full registry', $dry['status'] === 'dry_run');
        $ok('reconciliation balanced in dry-run', $dry['reconciliation']['balanced']);

        // 2. committed run — real GL postings + real pool contributions
        $res = $joiner->run(config_path(), fn () => 100000.00, 'conservative', true, $poolId);
        $ok('committed run posts', $res['status'] === 'posted');

        // 3. idempotency — same batch again is a no-op
        $again = $joiner->run(config_path(), fn () => 100000.00, 'conservative', true, $poolId);
        $ok('repeat run is already_posted (no double-posting)', $again['status'] === 'already_posted');

        // 4. persisted postings match the plan 1:1
        $stored = DB::table('joiner_postings')->where('batch_id', $res['batch_id'])->count();
        $ok('joiner_postings rows == planned postings', $stored === $res['postings']);

        // 5. unified ledger still balanced after the joiner posted
        $tb = $reporting->trialBalance();
        $ok('trial balance balanced after joiner posting', $tb['balanced']);

        // 6. pool received every contribution
        $contribs = DB::table('pool_contributions')->where('mother_pool_id', $poolId)->count();
        $ok('pool contributions recorded', $contribs === $res['contributions']);

        $this->info('ALL JOINER SELF-TESTS PASSED');
        $this->line('cleanup: php artisan kicc:selftest-joiner --cleanup');
        return self::SUCCESS;
    }

    private function cleanup(): int
    {
        $c = 0;
        foreach (DB::table('pools')->where('name', 'like', 'SELFTEST-JOINER-%')->get() as $p) {
            $c += DB::table('pool_contributions')->where('mother_pool_id', $p->id)->delete();
            $c += DB::table('pools')->where('id', $p->id)->delete();
        }
        foreach (DB::table('joiner_runs')->get() as $run) {
            $c += DB::table('joiner_postings')->where('batch_id', $run->batch_id)->delete();
            $c += DB::table('joiner_reconciliations')->where('batch_id', $run->batch_id)->delete();
            $c += DB::table('joiner_runs')->where('id', $run->id)->delete();
        }
        $this->info("cleaned {$c} SELFTEST-JOINER rows");
        return self::SUCCESS;
    }
}
