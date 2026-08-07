<?php

namespace App\Services;

use App\Models\MediaAsset;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;

/**
 * MediaLibraryService — the single source of truth for all media.
 *
 * Every uploaded file becomes a MediaAsset row; nothing is ever hardcoded.
 * Adapters (county hero, product image, venue cover) reference assets, so
 * swapping an image in admin automatically updates every public page.
 */
class MediaLibraryService
{
    public function store(UploadedFile $file, array $opts = []): MediaAsset
    {
        $uuid = (string) Str::uuid();
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin');
        $dir = $opts['directory'] ?? 'media';
        $path = $file->storeAs($dir, "{$uuid}.{$ext}", ['disk' => 'public']);

        $kind = match (strtolower($file->getClientMimeType() ?: $file->getMimeType() ?: '')) {
            'video/webm', 'video/mp4', 'video/quicktime' => 'video',
            'model/gltf-binary', 'model/gltf+json' => 'model',
            'audio/mpeg', 'audio/ogg' => 'audio',
            default => 'image',
        };

        $size = getimagesize(Storage::disk('public')->path($path));

        return MediaAsset::create([
            'uuid' => $uuid,
            'owner_id' => $opts['owner_id'] ?? auth()->id(),
            'owner_type' => $opts['owner_type'] ?? (auth()->user() ? get_class(auth()->user()) : null),
            'disk' => 'public',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType() ?: $file->getMimeType(),
            'kind' => $kind,
            'size_bytes' => $file->getSize(),
            'width' => $size[0] ?? null,
            'height' => $size[1] ?? null,
            'status' => 'uploaded',
            'alt_text' => $opts['alt_text'] ?? null,
            'metadata' => $opts['metadata'] ?? null,
        ]);
    }

    public function storeFromPath(string $absPath, array $opts = []): MediaAsset
    {
        $file = new \Illuminate\Http\File($absPath);

        return $this->store(new UploadedFile($file->getPathname(), $file->getFilename(), $file->getMimeType(), null, true), $opts);
    }

    public function url(MediaAsset $asset): string
    {
        return media($asset->path);
    }

    public function syncFromPath(MediaAsset $asset, string $absPath): MediaAsset
    {
        $rel = 'media/' . $asset->uuid . '.' . pathinfo($absPath, PATHINFO_EXTENSION);
        Storage::disk('public')->put($rel, file_get_contents($absPath));

        $asset->forceFill(['path' => $rel, 'size_bytes' => filesize($absPath)])->save();

        return $asset;
    }

    public function delete(MediaAsset $asset): void
    {
        foreach ($asset->derivatives as $d) {
            Storage::disk('public')->delete($d->path);
        }
        Storage::disk('public')->delete($asset->path);
        $asset->delete();
    }

    public function copyTo(MediaAsset $source, string $directory = 'media'): MediaAsset
    {
        $abs = Storage::disk($source->disk)->path($source->path);
        $ext = pathinfo($abs, PATHINFO_EXTENSION);
        $uuid = (string) Str::uuid();
        $path = "{$directory}/{$uuid}.{$ext}";
        Storage::disk('public')->put($path, file_get_contents($abs));

        return MediaAsset::create($source->only(['original_name', 'mime', 'kind', 'size_bytes', 'width', 'height', 'metadata']) + [
            'uuid' => $uuid,
            'owner_id' => $source->owner_id,
            'owner_type' => $source->owner_type,
            'disk' => 'public',
            'path' => $path,
            'status' => 'ready',
        ]);
    }
}
