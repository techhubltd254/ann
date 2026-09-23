<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class KiccRegisterPipelines extends Command
{
    protected $signature = 'kicc:register-pipelines';
    protected $description = 'Sync all 50 parent pipelines from config into pipeline_registrations';

    public function handle(): int
    {
        $pipelines = require base_path('config/kicc-pipelines.php');
        $count = 0;
        foreach ($pipelines as $p) {
            $code = $p['code'];
            $parent = preg_replace('/[0-9].*$/', '', $code);
            $slug = Str::slug($p['description'] ?? $code);

            DB::table('pipeline_registrations')->updateOrInsert(
                ['code' => $code],
                [
                    'parent' => $parent,
                    'sector' => $p['sector'] ?? 'general',
                    'slug' => $slug,
                    'phase' => $p['phase'] ?? '1',
                    'status' => $p['status'] ?? 'absent',
                    'economics' => json_encode(['take_rate' => $p['take_rate'] ?? '', 'model' => $p['economics']['model'] ?? 'commission']),
                    'regulators' => json_encode($p['regulators'] ?? []),
                    'tables' => json_encode($p['tables'] ?? []),
                    'kill_criteria' => json_encode(['max_dispute_rate_pct' => 3, 'min_fill_rate_pct' => 70, 'consecutive_months' => 2]),
                    'earning_locked' => in_array($p['status'] ?? '', ['licence_gated', 'blocked']),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
            $count++;
        }
        $this->info("Registered/updated {$count} pipelines.");
        return 0;
    }
}
