<?php

namespace App\Services;

use App\Models\MediaAsset;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;

class MediaLibraryService
{
    public function store(UploadedFile $file, array $opts = []): MediaAsset
    {
        $uuid = (string) Str::uuid();
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin');
        $dir = $opts['directory'] ?? 'media';
        $disk = $opts['disk'] ?? 'public';
        $path = $dir . "/{$uuid}.{$ext}";

        $stream = fopen($file->getRealPath(), 'r');
        Storage::disk($disk)->writeStream($path, $stream, ['visibility' => 'public']);
        if (is_resource($stream)) fclose($stream);

        $kind = match (strtolower($file->getClientMimeType() ?: $file->getMimeType() ?: '')) {
            'video/webm', 'video/mp4', 'video/quicktime' => 'video',
            'model/gltf-binary', 'model/gltf+json' => 'model',
            'audio/mpeg', 'audio/ogg' => 'audio',
            default => 'image',
        };

        [$width, $height] = $this->detectDimensions($file, $kind);

        return MediaAsset::create([
            'uuid' => $uuid,
            'owner_id' => $opts['owner_id'] ?? auth()->id(),
            'owner_type' => $opts['owner_type'] ?? (auth()->user() ? get_class(auth()->user()) : null),
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType() ?: $file->getMimeType(),
            'kind' => $kind,
            'size_bytes' => $file->getSize(),
            'width' => $width,
            'height' => $height,
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
        $ext = pathinfo($absPath, PATHINFO_EXTENSION);
        $path = 'media/' . $asset->uuid . '.' . $ext;
        Storage::disk($asset->disk)->writeStream($path, fopen($absPath, 'r'));

        $asset->forceFill(['path' => $path, 'size_bytes' => filesize($absPath)])->save();

        return $asset;
    }

    public function delete(MediaAsset $asset): void
    {
        $disk = Storage::disk($asset->disk);
        foreach ($asset->derivatives as $d) {
            $disk->delete($d->path);
        }
        $disk->delete($asset->path);
        $asset->delete();
    }

    public function copyTo(MediaAsset $source, string $directory = 'media', ?string $disk = null): MediaAsset
    {
        $targetDisk = $disk ?? $source->disk;
        $abs = Storage::disk($source->disk)->path($source->path);
        $ext = pathinfo($abs, PATHINFO_EXTENSION);
        $uuid = (string) Str::uuid();
        $path = "{$directory}/{$uuid}.{$ext}";
        Storage::disk($targetDisk)->writeStream($path, fopen($abs, 'r'));

        return MediaAsset::create($source->only(['original_name', 'mime', 'kind', 'size_bytes', 'width', 'height', 'metadata']) + [
            'uuid' => $uuid,
            'owner_id' => $source->owner_id,
            'owner_type' => $source->owner_type,
            'disk' => $targetDisk,
            'path' => $path,
            'status' => 'ready',
        ]);
    }

    private function detectDimensions(UploadedFile $file, string $kind): array
    {
        if ($kind === 'video') {
            try {
                $result = Process::run(
                    "ffprobe -v error -select_streams v:0 -show_entries stream=width,height -of csv=p=0 " . escapeshellarg($file->getRealPath())
                );
                if ($result->successful()) {
                    $parts = explode(',', trim($result->output()));
                    return [(int) ($parts[0] ?? 0) ?: null, (int) ($parts[1] ?? 0) ?: null];
                }
            } catch (\Throwable) {
            }
            return [null, null];
        }

        $size = @getimagesize($file->getRealPath());
        return $size ? [$size[0], $size[1]] : [null, null];
    }
}