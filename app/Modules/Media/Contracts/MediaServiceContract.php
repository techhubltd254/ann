<?php

namespace App\Modules\Media\Contracts;

/**
 * Media Service Contract — the public API surface of the Media module.
 * All external code (controllers, other modules) must call this contract,
 * never the concrete services inside App\Services\ (VideoService,
 * HlsGenerator, MediaLibraryService, CloudflareStreamService, etc.).
 *
 * This is the boundary: Media module owns its tables (media_assets,
 * media_derivatives, media_jobs) — other modules access media through
 * this contract only.
 */
interface MediaServiceContract
{
    /** Store an uploaded file and return the MediaAsset ID. */
    public function upload(string $ownerType, int $ownerId, string $slot, $file): int;

    /** Generate HLS ladder for a video asset. Returns job ID. */
    public function generateHls(int $assetId): string;

    /** Resolve the best URL for a media slot (owner + slot → URL). */
    public function resolve(string $ownerType, int $ownerId, string $slot): ?string;

    /** Delete an asset and its derivatives. */
    public function delete(int $assetId): void;

    /** Get all derivatives for an asset. */
    public function derivatives(int $assetId): array;
}