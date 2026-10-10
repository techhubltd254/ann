<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MediaAsset extends Model
{
    protected $fillable = [
        'uuid', 'owner_id', 'owner_type', 'slot', 'display_mode', 'disk', 'path', 'original_name',
        'mime', 'kind', 'size_bytes', 'width', 'height', 'status', 'alt_text', 'metadata',
        'contentType', 'uploadedByUserId', 'createdAt', 'originalName', 'sizeBytes', 'storageKey',
    ];

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
        return $this->hasMany(MediaDerivative::class)
            ->orderByRaw("FIELD(variant, 'source', 'adaptive', 'master', '2160p', '1440p', '1080p', '720p', '480p', '360p', '240p')");
    }

    public function pipelineJobs(): HasMany
    {
        return $this->hasMany(PipelineJob::class);
    }

    public function url(?string $variant = null): string
    {
        // Bytes live in R2. When MEDIA_CDN_URL is configured we use it;
        // otherwise we serve through the application proxy, which streams the
        // R2 object with Range support and is verified working on production.
        // The old hard-coded Worker host 404'd for every object.
        return $this->resolve($this->path);
    }

    public function bestVideoUrl(): ?string
    {
        $webm = $this->derivatives->firstWhere('kind', 'video_webm');
        if ($webm) return $this->resolve($webm->path);
        $mp4 = $this->derivatives->firstWhere('kind', 'video_mp4');
        if ($mp4) return $this->resolve($mp4->path);
        return $this->url();
    }

    public function derivativeUrl(string $kind, ?string $variant = null): ?string
    {
        $derivative = $variant === null
            ? $this->derivatives->firstWhere('kind', $kind)
            : $this->derivatives->first(fn($d) => $d->kind === $kind && $d->variant === $variant);
        return $derivative ? $this->resolve($derivative->path) : null;
    }

    public function webmUrl(): ?string
    {
        return $this->derivativeUrl('video_webm');
    }

    public function mp4Url(): ?string
    {
        $url = $this->derivativeUrl('video_mp4');
        if ($url) return $url;
        // Fallback: use the raw asset path directly when no derivative exists
        $direct = $this->url();
        if ($direct) {
            $ext = strtolower(pathinfo($this->path ?? '', PATHINFO_EXTENSION));
            if (str_contains($this->mime ?? '', 'mp4') || $ext === 'mp4') return $direct;
        }
        return null;
    }

    public function thumbnailUrl(): ?string
    {
        $thumb = $this->derivatives->firstWhere('kind', 'thumb');
        if ($thumb) return $this->resolve($thumb->path);
        $webp = $this->derivatives->firstWhere('kind', 'webp');
        if ($webp) return $this->resolve($webp->path);
        return null;
    }

    public function glbUrl(): ?string
    {
        $glb = $this->derivatives->firstWhere('kind', 'model_glb');
        return $glb ? $this->resolve($glb->path) : null;
    }

    /** Tier 1: ultra-light WebP poster frame for grid cards */
    public function posterUrl(): ?string
    {
        $poster = $this->derivatives->firstWhere('kind', 'poster');
        return $poster ? $this->resolve($poster->path) : null;
    }

    /** Tier 2: 3s low-bitrate hover/in-view loop */
    public function hoverLoopUrl(): ?string
    {
        $loop = $this->derivatives->firstWhere('kind', 'hover_loop');
        if ($loop) return $this->resolve($loop->path);
        return $this->mp4Url();
    }

    /** Tier 3: interactive 4D Gaussian splat (detail page only) */
    public function splatUrl(): ?string
    {
        $splat = $this->derivatives->firstWhere('kind', 'model_splat');
        return $splat ? $this->resolve($splat->path) : null;
    }

    public function hasDepthMap(): bool
    {
        return $this->derivatives->contains('kind', 'depth_map');
    }

    public function depthMapUrl(): ?string
    {
        return $this->derivativeUrl('depth_map');
    }

    protected function resolve(string $path): string
    {
        $cdn = media_url();
        if ($cdn !== '') return $cdn . '/' . ltrim($path, '/');
        if (($this->disk ?? 'r2') === 'r2') return url((str_ends_with(strtolower($path),'.m3u8')?'/media/video/':'/media/original/') . ltrim($path, '/'));
        return url('/storage/' . ltrim($path, '/'));
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
            ->latest('id')
            ->lockForUpdate()
            ->first();
    }

    protected static function booted(): void
    {
        static::creating(function (self $m) {
            // TiDB raw-schema duplicates some columns in camelCase NOT NULL
            // without defaults (contentType mirrors mime). Fill known mirrors
            // and then schema-introspect for any remaining NOT NULL no-defaults.
            if ($m->getAttribute('contentType') === null && $m->getAttribute('mime') !== null) {
                $m->setAttribute('contentType', $m->getAttribute('mime'));
            }

            static $required = null;
            if ($required === null) {
                $required = [];
                try {
                    $cols = \Illuminate\Support\Facades\DB::select('SHOW COLUMNS FROM media_assets');
                    foreach ($cols as $c) {
                        $isNull = ($c->Null ?? '') === 'YES';
                        $hasDefault = isset($c->Default) && $c->Default !== null;
                        if (!$isNull && !$hasDefault && !in_array($c->Field, ['id', 'created_at', 'updated_at', 'uuid'], true)) {
                            $required[] = $c->Field;
                        }
                    }
                } catch (\Throwable $e) {
                    $required = [];
                }
            }
            foreach ($required as $col) {
                if ($m->getAttribute($col) !== null) continue;
                if (str_ends_with($col, '_id') || (str_ends_with($col, 'Id') && $col !== 'uploadedByUserId')) continue;
                if (str_contains($col, 'At') || str_contains($col, 'Date')) {
                    $m->setAttribute($col, now());
                } elseif (in_array($col, ['status'], true)) {
                    $m->setAttribute($col, 'ready');
                } elseif (in_array($col, ['size_bytes', 'sizeBytes', 'width', 'height'], true)) {
                    $m->setAttribute($col, 0);
                } elseif ($col === 'uploadedByUserId') {
                    $m->setAttribute($col, auth()->id() ?? 1);
                } elseif (in_array($col, ['contentType'], true)) {
                    $m->setAttribute($col, $m->getAttribute('mime') ?? 'application/octet-stream');
                } else {
                    $m->setAttribute($col, '');
                }
            }
        });
    }
}
