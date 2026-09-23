<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class KiccRegisterPipelines extends Command
{
    protected $signature = 'kicc:register-pipelines';
    protected $description = 'Sync ALL pipelines — 50 parents + 152 subsectors = 202 — into pipeline_registrations';

    public function handle(): int
    {
        $count = 0;

        // 1. Register the 50 parent pipelines
        $parents = require base_path('config/kicc-pipelines.php');
        foreach ($parents as $p) {
            $this->upsert($p['code'], [
                'parent' => preg_replace('/[0-9].*$/', '', $p['code']),
                'sector' => $p['sector'] ?? 'general',
                'slug' => Str::slug($p['description'] ?? $p['code']),
                'phase' => $p['phase'] ?? '1',
                'status' => $p['status'] ?? 'absent',
                'economics' => json_encode(['take_rate' => $p['take_rate'] ?? '']),
                'regulators' => json_encode($p['regulators'] ?? []),
                'tables' => json_encode($p['tables'] ?? []),
                'kill_criteria' => json_encode(['max_dispute_rate_pct' => 3, 'min_fill_rate_pct' => 70, 'consecutive_months' => 2]),
                'earning_locked' => in_array($p['status'] ?? '', ['licence_gated', 'blocked']),
            ]);
            $count++;
        }

        // 2. Register the 152 subsector pipelines from config/kicc/subsectors/
        $indexPath = base_path('config/kicc/subsectors/index.php');
        if (file_exists($indexPath)) {
            $subs = require $indexPath;
            foreach ($subs as $s) {
                $model = $s['economics']['model'] ?? 'commission';
                $rate = json_encode($s['economics']['take_rate_pct'] ?? $s['economics']['take_rate'] ?? null);
                $this->upsert($s['code'], [
                    'parent' => $s['parent'] ?? preg_replace('/\.\d+$/', '', $s['code']),
                    'sector' => $s['sector'] ?? 'general',
                    'slug' => $s['slug'] ?? Str::slug($s['subsector'] ?? $s['code']),
                    'phase' => $s['phase'] ?? '1',
                    'status' => $s['status'] ?? 'absent',
                    'economics' => json_encode(['model' => $model, 'take_rate_pct' => json_decode($rate, true)]),
                    'regulators' => json_encode($s['regulators'] ?? []),
                    'tables' => json_encode($s['tables'] ?? []),
                    'kill_criteria' => json_encode($s['kill_criteria'] ?? ['max_dispute_rate_pct' => 3, 'min_fill_rate_pct' => 70, 'consecutive_months' => 2]),
                    'earning_locked' => in_array($s['status'] ?? '', ['licence_gated', 'blocked'])
                        || !empty($s['engine']['ledger']['earning_locked']),
                ]);
                $count++;
            }
        }

        $this->info("Registered/updated {$count} pipelines (50 parents + 152 subsectors).");
        return 0;
    }

    private function upsert(string $code, array $data): void
    {
        DB::table('pipeline_registrations')->updateOrInsert(
            ['code' => $code],
            array_merge($data, ['updated_at' => now(), 'created_at' => now()])
        );
    }
}
