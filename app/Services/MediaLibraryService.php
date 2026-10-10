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
        $ext=strtolower($file->getClientOriginalExtension());$mime=$file->getMimeType();
        $images=['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp'];
        if(isset($images[$ext]))abort_unless($mime===$images[$ext]&&getimagesize($file->getRealPath())!==false,422,'Invalid image content.');
        elseif($ext==='glb'){$h=fopen($file->getRealPath(),'rb');$magic=fread($h,4);fclose($h);abort_unless($magic==='glTF',422,'Invalid GLB content.');}
        elseif($ext==='gltf'){$model=json_decode(file_get_contents($file->getRealPath()),true);abort_unless(is_array($model)&&($model['asset']['version']??'')==='2.0',422,'Invalid GLTF content.');foreach(array_merge($model['buffers']??[],$model['images']??[]) as $resource)if(isset($resource['uri']))abort_unless(str_starts_with($resource['uri'],'data:'),422,'External GLTF resources are not supported; upload a self-contained GLB.');}
        elseif(in_array($ext,['mp4','webm','mov'],true))abort_unless(in_array($mime,['video/mp4','video/webm','video/quicktime','application/mp4'],true),422,'Invalid video content.');
        elseif(in_array($ext,['mp3','ogg'],true))abort_unless(in_array($mime,['audio/mpeg','audio/ogg','application/ogg'],true),422,'Invalid audio content.');
        else abort(422,'This file type cannot be published.');

        $uuid = (string) Str::uuid();
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin');
        $dir = $opts['directory'] ?? 'media';
        $disk = $opts['disk'] ?? 'public';
        $path = $dir . "/{$uuid}.{$ext}";

        $stream = fopen($file->getRealPath(), 'r');
        Storage::disk($disk)->writeStream($path, $stream, ['visibility' => 'public']);
        if (is_resource($stream)) fclose($stream);

        $kind = match (strtolower($file->getMimeType() ?: '')) {
            'video/webm', 'video/mp4', 'video/quicktime' => 'video',
            'model/gltf-binary', 'model/gltf+json' => 'model',
            'audio/mpeg', 'audio/ogg' => 'audio',
            default => 'image',
        };

        [$width, $height] = $this->detectDimensions($file, $kind);

        // Write metadata sidecar file for future sync (used by media:sync-from-r2)
        if ($disk === 'r2' && !empty($opts['owner_type'])) {
            try {
                Storage::disk($disk)->put($path . '.meta.json', json_encode([
                    'owner_type' => $opts['owner_type'] ?? null,
                    'owner_id' => $opts['owner_id'] ?? null,
                    'slot' => $opts['slot'] ?? null,
                    'uploaded_at' => now()->toIso8601String(),
                ]), ['visibility' => 'public']);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('meta sidecar write failed: ' . $e->getMessage());
            }
        }

        return MediaAsset::create([
            'uuid' => $uuid,
            'owner_id' => $opts['owner_id'] ?? auth()->id(),
            'owner_type' => $opts['owner_type'] ?? (auth()->user() ? get_class(auth()->user()) : null),
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'kind' => $kind,
            'size_bytes' => $file->getSize(),
            'width' => $width,
            'height' => $height,
            'status' => 'uploaded',
            'alt_text' => $opts['alt_text'] ?? null,
            'metadata' => $opts['metadata'] ?? null,
            // A slot is the whole point of a tile upload: without it the asset
            // is stored but never binds to the tile it was uploaded for.
            'slot' => $opts['slot'] ?? null,
            'display_mode' => $opts['display_mode'] ?? null,
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