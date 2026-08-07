<?php

namespace App\Console\Commands;

use App\Models\SectorEntity;
use App\Models\County;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AiPipelineLearner extends Command
{
    protected $signature = 'ai:pipeline-learner';
    protected $description = 'Generate recommendations based on entity data';

    public function handle()
    {
        $this->info('Running AI Pipeline Learner...');
        $counties = County::all();

        foreach ($counties as $county) {
            $entities = SectorEntity::where('county_id', $county->id)
                ->whereNotNull('name')
                ->take(20)
                ->get();

            foreach ($entities as $entity) {
                $score = 0;
                if ($entity->description) $score += 25;
                if ($entity->sector_type) $score += 25;
                if ($entity->capture_status === 'tier_a') $score += 30;
                if ($entity->capture_status === 'tier_b') $score += 15;

                if ($score >= 50) {
                    DB::table('recommendations')->insert([
                        'user_id' => 1,
                        'type' => 'entity',
                        'title' => $entity->name,
                        'items' => json_encode(['entity_id' => $entity->id, 'county_id' => $entity->county_id, 'score' => $score]),
                        'model_id' => $entity->id,
                        'context' => $this->reason($score),
                        'expires_at' => now()->addDays(7),
                        'created_at' => now(),
                    ]);
                }
            }
        }

        $matches = DB::table('recommendations')->whereDate('expires_at', '>', now())->count();
        $this->info("  Generated {$matches} active recommendations");
    }

    private function reason(int $score): string
    {
        return match (true) {
            $score >= 70 => 'Complete profile with tier_a verification',
            $score >= 50 => 'Good profile completeness',
            $score >= 30 => 'Basic profile with some data',
            default => 'Minimal data — needs enrichment',
        };
    }
}
