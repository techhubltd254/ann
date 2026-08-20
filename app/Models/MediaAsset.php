<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MediaAsset extends Model
{
    protected $fillable = [
        'uuid', 'owner_id', 'owner_type', 'slot', 'disk', 'path', 'original_name',
        'mime', 'kind', 'size_bytes', 'width', 'height', 'status', 'alt_text', 'metadata',
        'contentType', 'createdAt', 'originalName', 'sizeBytes', 'storageKey',
        'thumbKey', 'uploadedByUserId',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $asset) {
            $asset->contentType ??= $asset->mime;
            $asset->createdAt ??= (string) now();
            $asset->originalName ??= $asset->original_name;
            $asset->sizeBytes ??= $asset->size_bytes;
            $asset->storageKey ??= $asset->path;
            $asset->uploadedByUserId ??= 0;
        });
    }

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'metadata' => 'json',
        ];
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function derivatives(): HasMany
    {
        return $this->hasMany(MediaDerivative::class);
    }

    public function pipelineJobs(): HasMany
    {
        return $this->hasMany(PipelineJob::class);
    }

    public function url(?string $variant = null): string
    {
        $path = $variant ? str_replace('/originals/', '/', $this->path) : $this->path;
        $media = media_url();

        return $media . '/' . ltrim($path, '/');
    }

    public function bestVideoUrl(): ?string
    {
        $preferred = $this->derivatives()
            ->whereIn('kind', ['video_webm', 'video_mp4'])
            ->orderByRaw("FIELD(kind, 'video_webm', 'video_mp4')")
            ->value('path');

        return $preferred ? $this->resolve($preferred) : null;
    }

    public function derivativeUrl(string $kind, ?string $variant = null): ?string
    {
        $query = $this->derivatives()->where('kind', $kind);
        if ($variant) {
            $query->where('variant', $variant);
        }

        $path = $query->value('path');

        return $path ? $this->resolve($path) : null;
    }

    public function webmUrl(): ?string
    {
        return $this->derivativeUrl('video_webm');
    }

    public function mp4Url(): ?string
    {
        return $this->derivativeUrl('video_mp4');
    }

    public function posterUrl(): ?string
    {
        $poster = $this->derivatives()->where('kind', 'poster')->value('path');

        return $poster ? $this->resolve($poster) : null;
    }

    public function thumbnailUrl(): ?string
    {
        $thumb = $this->derivatives()->where('kind', 'thumb')->value('path')
            ?? $this->derivatives()->where('kind', 'webp')->value('path');

        return $thumb ? $this->resolve($thumb) : null;
    }

    public function glbUrl(): ?string
    {
        $glb = $this->derivatives()
            ->where('kind', 'model_glb')
            ->orderBy('variant')
            ->value('path');

        return $glb ? $this->resolve($glb) : null;
    }

    protected function resolve(string $path): string
    {
        return media_url() . '/' . ltrim($path, '/');
    }

    public function scopeKind(Builder $q, string $kind): Builder
    {
        return $q->where('kind', $kind);
    }

    public function scopeReady(Builder $q): Builder
    {
        return $q->where('status', 'ready');
    }

    public function scopeForSlot(Builder $q, string $ownerType, int $ownerId, string $slot): Builder
    {
        return $q->where('owner_type', $ownerType)
            ->where('owner_id', $ownerId)
            ->where('slot', $slot);
    }

    public static function resolveSlot(string $ownerType, int $ownerId, string $slot): ?self
    {
        return static::query()
            ->forSlot($ownerType, $ownerId, $slot)
            ->ready()
            ->with('derivatives')
            ->latest()
            ->first();
    }
}
