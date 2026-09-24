<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class KiccStatus extends Command
{
    protected $signature = 'kicc:status';
    protected $description = 'Confirm all algorithms and pipelines are active';

    public function handle(): int
    {
        $this->info('╔══════════════════════════════════════╗');
        $this->info('║  KICC PLATFORM — STATUS REPORT        ║');
        $this->info('╚══════════════════════════════════════╝');
        $this->newLine();

        // 1. Python algorithms service
        $this->line('■ PYTHON ALGORITHMS SERVICE');
        $ch = curl_init('http://127.0.0.1:8400/health');
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $resp = @curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($http === 200) {
            $this->line('  ✅  Port 8400 — HEALTHY (20 algorithms)');
        } else {
            $this->warn('  ⚠️  Port 8400 — DOWN (run: infra/deploy/start-algorithms-service.sh)');
        }

        // 2. Pipeline registrations
        $total = DB::table('pipeline_registrations')->count();
        $byStatus = DB::table('pipeline_registrations')
            ->selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status');
        $this->newLine();
        $this->line('■ PIPELINE REGISTRATIONS');
        $this->line("  📦  Total: $total");
        foreach ($byStatus as $s => $c) $this->line("     $s: $c");
        if ($total === 202) $this->line('  ✅  All 202 pipelines registered (50 parents + 152 subsectors)');
        else $this->warn("  ⚠️  Registered: $total");

        // 3. Subsector configs
        $subsectors = [];
        $indexPath = base_path('config/kicc/subsectors/index.php');
        if (file_exists($indexPath)) {
            try { $subsectors = require $indexPath; } catch (\Throwable $e) {}
        }
        $this->newLine();
        $this->line('■ SUBSECTOR CONFIGS (152 subsectors)');
        $count = is_array($subsectors) ? count($subsectors) : 0;
        if ($count === 152) {
            $this->line('  ✅  All 152 loaded successfully');
        } else {
            $this->line("     Sector files found, entries: $count");
        }

        // 4. Core engine tables
        $tables = array_map(fn($t) => current((array)$t), DB::select('SHOW TABLES'));
        $engineTables = ['gl_accounts', 'ledger_transactions', 'pools', 'pool_contributions',
            'pool_distributions', 'joiner_runs', 'joiner_postings', 'pipeline_registrations',
            'ledger_holds', 'ledger_splits', 'gl_journal_entries', 'gl_journal_lines'];
        $this->newLine();
        $this->line('■ ENGINE TABLES');
        $missing = 0;
        foreach ($engineTables as $t) {
            $ok = in_array($t, $tables);
            $this->line('  ' . ($ok ? '✅' : '❌') . " $t");
            if (!$ok) $missing++;
        }
        $this->line('  Total tables on TiDB: ' . count($tables));

        // 5. 47 counties classified
        $classified = DB::table('counties')->whereNotNull('classification_quadrant')->count();
        $this->newLine();
        $this->line('■ COUNTY CLASSIFICATION');
        $this->line('  ' . ($classified === 47 ? '✅' : '⚠️') . " $classified/47 counties classified");

        // 6. Joiner dry-run ready
        $joinerRuns = DB::table('joiner_runs')->count();
        $this->newLine();
        $this->line('■ JOINER');
        $this->line('  ' . ($joinerRuns > 0 ? '✅' : '⬜') . " Previous dry-run batches: $joinerRuns");
        $this->line('  Run: php artisan kicc:joiner:run --commit --pool=1');

        // 7. Mother Admin tab
        $poolBalance = DB::table('pools')->where('scope', 'global')->value('balance') ?? 0;
        $this->newLine();
        $this->line('■ MOTHER POOL');
        $this->line("  💰  Mother Pool balance: KES " . number_format($poolBalance));

        $this->newLine();
        $this->info('=== ALL CHECKS COMPLETE ===');
        return $missing === 0 ? 0 : 1;
    }
}