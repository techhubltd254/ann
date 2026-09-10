<?php

namespace App\Jobs;

use App\Models\CountyInstitution;
use App\Services\InstitutionSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncInstitutionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 3;
    public $backoff = [30, 120, 300];

    public function __construct(
        public int $institutionId,
    ) {
        $this->queue = 'sync';
    }

    public function handle(InstitutionSyncService $syncService): void
    {
        $institution = CountyInstitution::find($this->institutionId);
        if (!$institution) {
            Log::warning("SyncInstitutionJob: institution {$this->institutionId} not found");
            return;
        }

        $summary = $syncService->sync($institution);

        Log::info("SyncInstitutionJob: synced {$institution->slug} — " . json_encode($summary));

        try {
            \App\Services\N8nService::fire('institution_auto_synced', [
                'institution' => $institution->slug,
                'summary' => $summary,
            ]);
        } catch (\Throwable $e) {
            // non-fatal
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error("SyncInstitutionJob: failed for institution {$this->institutionId}: " . $e->getMessage());
    }
}