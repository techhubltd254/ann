<?php

namespace App\Console\Commands;

use App\Kicc\Services\JoinerService;
use Illuminate\Console\Command;

/**
 * Run the Mother-Pipeline Joiner over the whole subsector registry.
 *
 * Planning mode (default):  php artisan kicc:joiner:run
 * Optimistic rates:         php artisan kicc:joiner:run --rate=optimistic
 * One sector only:          php artisan kicc:joiner:run --sector=tourism
 * Commit to GL + pool:      php artisan kicc:joiner:run --commit --pool=1
 */
class KiccJoinerRun extends Command
{
    protected $signature = 'kicc:joiner:run
        {--dry-run : Preview only (default)}
        {--commit : Persist run, post unified journals and record contributions}
        {--sector= : Restrict to one sector}
        {--rate=conservative : conservative|optimistic take-rate bound}
        {--gmv=100000 : Synthetic GMV per subsector (planning scenario input)}
        {--pool= : Mother-Pool id to receive the contributions on --commit}';

    protected $description = 'Mother-Pipeline Joiner: orchestrate + reconcile all subsector pipelines into unified GL and Mother-Pool postings';

    public function handle(JoinerService $joiner): int
    {
        $gmv = (float) $this->option('gmv');
        $commit = (bool) $this->option('commit');

        $result = $joiner->run(
            config_path(),
            fn (string $code, array $entry) => $gmv, // planning scenario: same GMV per subsector
            $this->option('rate') === 'optimistic' ? 'optimistic' : 'conservative',
            $commit,
            $this->option('pool') ? (int) $this->option('pool') : null,
            $this->option('sector')
        );

        $recon = $result['reconciliation'];
        $t = $recon['totals'];

        $this->info('Joiner status: ' . $result['status'] . '  batch: ' . $result['batch_id']);
        if (isset($result['postings'])) {
            $this->line('postings: ' . $result['postings'] . '  contributions: ' . $result['contributions']);
        }
        $this->line(sprintf('GMV %.2f | fees %.2f | holdback(10%%) %.2f | equalisation(0.5%%) %.2f | kicc_net %.2f | vendor_payout %.2f',
            $t['gmv'], $t['fees'], $t['holdback'], $t['equalisation'], $t['kicc_net'], $t['vendor_payout']));
        $this->line(sprintf('unified ledger: debits %.2f vs credits %.2f — %s',
            $recon['accounts']['total_debits'], $recon['accounts']['total_credits'],
            $recon['accounts']['balanced'] ? 'BALANCED' : 'UNBALANCED'));

        if (!empty($recon['variance_report'])) {
            $this->error('VARIANCE REPORT:');
            foreach ($recon['variance_report'] as $v) $this->line('  - ' . $v);
            return self::FAILURE;
        }
        $this->info('Reconciliation: BALANCED — no variances.');
        return self::SUCCESS;
    }
}
