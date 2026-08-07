<?php

namespace App\Console\Commands;

use App\Models\AnalyticsEvent;
use App\Models\PipelineJob;
use App\Models\Recommendation;
use App\Models\SectorEntity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiPipelineObserver extends Command
{
    protected $signature = 'ai:pipeline-observer';
    protected $description = 'Run all AI pipeline observer jobs — SEO, traffic, quality, weather signals';

    public function handle()
    {
        $this->info('Running AI Pipeline Observer...');
        $this->observeSeoSignals();
        $this->observeTrafficSignals();
        $this->observeQualitySignals();
        $this->runDecider();
        $this->info('Observer cycle complete.');
    }

    private function observeSeoSignals(): void
    {
        // Check for entities with missing SEO metadata
        $missingSeo = SectorEntity::whereNull('description')
            ->orWhere('description', '')
            ->count();

        DB::table('pipeline_logs')->insert([
            'source' => 'observer',
            'signal' => 'seo_missing_descriptions',
            'value' => (string) $missingSeo,
            'severity' => $missingSeo > 100 ? 'warning' : 'info',
            'created_at' => now(),
        ]);
        $this->line("  SEO: {$missingSeo} entities missing descriptions");
    }

    private function observeTrafficSignals(): void
    {
        // Check recent page views
        $recentViews = DB::table('page_views')
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        DB::table('pipeline_logs')->insert([
            'source' => 'observer',
            'signal' => 'traffic_24h',
            'value' => (string) $recentViews,
            'severity' => 'info',
            'created_at' => now(),
        ]);
        $this->line("  Traffic: {$recentViews} page views in 24h");
    }

    private function observeQualitySignals(): void
    {
        // Check entity completion rate
        $total = SectorEntity::count();
        $complete = SectorEntity::whereNotNull('description')
            ->where('description', '!=', '')
            ->whereNotNull('sector_type')
            ->count();

        $completionPct = $total > 0 ? round($complete / $total * 100) : 0;

        DB::table('pipeline_logs')->insert([
            'source' => 'observer',
            'signal' => 'data_quality_completion',
            'value' => (string) $completionPct,
            'severity' => $completionPct < 50 ? 'warning' : 'info',
            'created_at' => now(),
        ]);
        $this->line("  Quality: {$completionPct}% entity completion rate");
    }

    private function runDecider(): void
    {
        // Rules engine: if quality below threshold, flag for content generation
        $latest = DB::table('pipeline_logs')
            ->where('signal', 'data_quality_completion')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($latest && (int) $latest->value < 60) {
            DB::table('pipeline_jobs')->insert([
                'type' => 'content_generation',
                'status' => 'pending',
                'priority' => 5,
                'payload' => json_encode([
                    'trigger' => 'low_quality_score',
                    'score' => (int) $latest->value,
                    'threshold' => 60,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->line('  Decider: Queued content generation job (quality below 60%)');
        }
    }
}
