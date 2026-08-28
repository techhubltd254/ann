<?php

namespace App\Jobs;

use App\Models\ImageVariant;
use App\Services\ImageOptimizer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Layer 3 ingestion job: fetch a raw image, generate WebP variants + blur,
 * upload to R2 and record them. Runs on the 'default' queue (light enough).
 */
class ImageVariantJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;
    public $tries = 2;
    public $backoff = [30, 120];

    public function __construct(
        public ?string $ownerType = null,
        public ?int $ownerId = null,
        public ?string $sourceUrl = null,
        public ?string $localPath = null,
        public ?string $r2BaseKey = null,
    ) {
        $this->queue = 'default';
    }

    public function handle(ImageOptimizer $optimizer): void
    {
        $temp = null;
        try {
            if ($this->localPath && file_exists($this->localPath)) {
                $temp = $this->localPath;
            } elseif ($this->sourceUrl) {
                $temp = tempnam(sys_get_temp_dir(), 'imgjob_') . '.img';
                $resp = Http::timeout(30)->get($this->sourceUrl);
                if (!$resp->ok()) {
                    Log::warning("ImageVariantJob: fetch failed {$this->sourceUrl} ({$resp->status()})");
                    return;
                }
                file_put_contents($temp, $resp->body());
            } else {
                return;
            }

            $base = $this->r2BaseKey ?? ($this->ownerType && $this->ownerId
                ? 'opt/' . Str::slug(class_basename($this->ownerType)) . '/' . $this->ownerId
                : 'opt/' . Str::random(10));

            $result = $optimizer->generateVariants($temp, $base);
            if (empty($result['variants'])) {
                Log::warning("ImageVariantJob: no variants generated for {$this->sourceUrl}");
                return;
            }

            // Record
            $record = ImageVariant::updateOrCreate(
                ['source_hash' => md5($this->sourceUrl ?? $this->localPath ?? $base)],
                [
                    'owner_type' => $this->ownerType,
                    'owner_id' => $this->ownerId,
                    'source_url' => $this->sourceUrl,
                    'thumb_key' => $result['variants']['thumb'] ?? null,
                    'card_key' => $result['variants']['card'] ?? null,
                    'hero_key' => $result['variants']['hero'] ?? null,
                    'blur' => $result['blur'],
                ]
            );

            Log::info("ImageVariantJob: variants ready for " . ($this->sourceUrl ?? $this->localPath) . " (record {$record->id})");
        } catch (\Throwable $e) {
            Log::error("ImageVariantJob failed: " . $e->getMessage());
        } finally {
            if ($temp && $temp !== $this->localPath && file_exists($temp)) {
                @unlink($temp);
            }
        }
    }
}