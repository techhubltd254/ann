<?php

namespace App\Modules\Media;

use App\Modules\Media\Contracts\MediaServiceContract;
use App\Services\MediaLibraryService;
use App\Services\HlsGenerator;
use App\Services\MediaFallbackResolver;
use Illuminate\Support\ServiceProvider;

/**
 * Media Module — bounded context for media assets, derivatives, and pipelines.
 *
 * Every piece of media (images, videos, 3D models) flows through this module.
 * Other modules access media ONLY through MediaServiceContract — never directly
 * through the underlying services.
 */
class MediaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind the contract to a concrete implementation.
        // Currently delegates to existing flat services; future: replace with
        // a dedicated MediaService that owns the media domain end-to-end.
        $this->app->singleton(MediaServiceContract::class, fn ($app) =>
            new class($app) implements MediaServiceContract {
                public function upload(string $ownerType, int $ownerId, string $slot, $file): int
                {
                    return app(MediaLibraryService::class)->upload($ownerType, $ownerId, $slot, $file)->id;
                }
                public function generateHls(int $assetId): string
                {
                    return app(HlsGenerator::class)->enqueue($assetId);
                }
                public function resolve(string $ownerType, int $ownerId, string $slot): ?string
                {
                    return app(MediaFallbackResolver::class)->resolveSlot($ownerType, $ownerId, $slot);
                }
                public function delete(int $assetId): void
                {
                    app(MediaLibraryService::class)->deleteAsset($assetId);
                }
                public function derivatives(int $assetId): array
                {
                    return app(MediaLibraryService::class)->derivatives($assetId)->toArray();
                }
            }
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/Routes/media.php');
    }
}