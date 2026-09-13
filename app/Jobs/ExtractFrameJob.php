<?php
namespace App\Jobs;

use App\Models\SectorEntity;
use App\Services\MediaFallbackResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExtractFrameJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(public string $videoUrl, public int $entityId, public ?string $existingPoster = null) {}

    public function handle(MediaFallbackResolver $resolver): void
    {
        $frame = $resolver->extractFrame($this->videoUrl);
        if ($frame && $this->existingPoster === null) {
            $entity = SectorEntity::find($this->entityId);
            \Illuminate\Support\Facades\Log::info("ExtractFrameJob: frame extracted for entity {$this->entityId}");
        }
    }
}