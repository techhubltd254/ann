<?php

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

if (!function_exists('media')) {
    function media(string $path = ''): string
    {
        return media_url() . '/' . ltrim($path, '/');
    }
}

if (!function_exists('media_url')) {
    function media_url(): string
    {
        // Empty string (not url('storage')) when no CDN is configured: callers
        // concatenate a leading '/', so '' yields same-origin /<path>, which the
        // media proxy serves from R2. url('storage') produced /storage/... and
        // 404s because nothing is mounted there.
        return rtrim((string) config('media.cdn_url'), '/');
    }
}

if (!function_exists('img_url')) {
    function img_url(?string $path, int $width = 0, string $format = 'auto'): string
    {
        if (!$path) return '';

        // Public-disk assets (storage/app/public → public/storage symlink):
        // a CDN base wins when configured, otherwise same-origin /storage.
        $storage = rtrim((string) config('media.cdn_url'), '/') ?: url('storage');

        if ($format === 'webp') {
            $webpPath = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $path);
            return "$storage/$webpPath";
        }

        return "$storage/$path";
    }
}

if (!function_exists('img')) {
    function img(?string $path, string $alt = '', string $class = '', int $width = 0, int $height = 0): string
    {
        if (!$path) return '';

        $storage = rtrim((string) config('media.cdn_url'), '/');
        $fallback = "$storage/$path";

        $webpPath = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $path);
        $webp = "$storage/$webpPath";

        $sizeAttrs = '';
        if ($width) $sizeAttrs .= " width=\"$width\"";
        if ($height) $sizeAttrs .= " height=\"$height\"";

        $loading = (!$width || $width > 300) ? 'loading="lazy"' : '';
        $decoding = 'decoding="async"';

        return "<picture>
            <source srcset=\"$webp\" type=\"image/webp\">
            <img src=\"$fallback\" alt=\"" . htmlspecialchars($alt) . "\" class=\"$class\" $sizeAttrs $loading $decoding>
        </picture>";
    }
}

if (!function_exists('img_srcset')) {
    function img_srcset(string $path, array $sizes = [320, 640, 1280]): string
    {
        $dir = dirname($path);
        $name = pathinfo($path, PATHINFO_FILENAME);
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        $storage = rtrim((string) config('media.cdn_url'), '/') ?: url('storage');

        $srcset = [];
        foreach ($sizes as $w) {
            $srcset[] = "$storage/$dir/{$name}_{$w}.$ext {$w}w";
        }

        return implode(', ', $srcset);
    }
}

if (!function_exists('img_size')) {
    function img_size(?string $path): ?int
    {
        if (!$path) return null;
        try {
            $fullPath = Storage::disk('public')->path($path);
            return file_exists($fullPath) ? filesize($fullPath) : null;
        } catch (\Throwable) {
            return null;
        }
    }
}

if (!function_exists('lottie')) {
    function lottie(string $icon, string $cls = ''): string
    {
        return '<lottie-player src="' . media('icons/' . $icon . '.json') . '" ' . $cls . ' autoplay loop mode="normal"></lottie-player>';
    }
}
if (!function_exists('image_blur')) {
    /**
     * Layer-1 blur-up: return the stored base64 blur placeholder for a source
     * image URL (from the ingestion pipeline), or null when unavailable.
     */
    function image_blur(?string $sourceUrl): ?string
    {
        if (!$sourceUrl) return null;
        try {
            $hash = md5($sourceUrl);
            return \Illuminate\Support\Facades\Cache::remember("iblur:{$hash}", 86400, fn() =>
                \App\Models\ImageVariant::where('source_hash', $hash)->value('blur')
            );
        } catch (\Throwable) {
            return null;
        }
    }
}

if (!function_exists('image_variant')) {
    /**
     * Layer-3: return a stored variant URL (thumb/card/hero) for a source image.
     */
    function image_variant(?string $sourceUrl, string $size = 'card'): ?string
    {
        if (!$sourceUrl) return null;
        try {
            $v = \App\Models\ImageVariant::where('source_hash', md5($sourceUrl))->latest('id')->first();
            if (!$v) return null;
            return match ($size) {
                'thumb' => $v->thumbUrl(),
                'hero' => $v->heroUrl(),
                default => $v->cardUrl(),
            };
        } catch (\Throwable) {
            return null;
        }
    }
}

if (!function_exists('cache_buster')) {
    function cache_buster(): string
    {
        $key = 'kicc_cache_version';
        $version = Illuminate\Support\Facades\Cache::remember($key, 86400, fn () => time());
        return (string) $version;
    }
}

if (!function_exists('bust_cache')) {
    function bust_cache(): void
    {
        $key = 'kicc_cache_version';
        Illuminate\Support\Facades\Cache::forever($key, time());
    }
}


if (!function_exists('tile_url')) {
    /**
     * The URL of one tile slot, resolved through media_assets.
     *
     * Nothing in a view should ever carry a literal media path: every tile —
     * brand mark, hero plate, editorial frame, per-entity fallback — comes from
     * a row an admin can upload, replace or delete.
     */
    function tile_url(string $slot, string $type = 'default', $id = null): string
    {
        static $resolver = null;
        if ($resolver === null) {
            $resolver = app(\App\Services\TileMediaResolver::class);
        }

        $map = [
            'institution' => \App\Models\CountyInstitution::class,
            'product'     => \App\Models\Marketplace\Product::class,
            'county'      => \App\Models\County::class,
            'venue'       => \App\Models\Venue::class,
            'landing'     => 'landing_page',
            'default'     => \App\Services\TileMediaResolver::OWNER_TYPE,
        ];

        $ownerType = $map[$type] ?? \App\Services\TileMediaResolver::OWNER_TYPE;
        $ownerId   = $type === 'default'
            ? \App\Services\TileMediaResolver::OWNER_ID
            : (int) $id;

        return (string) $resolver->url($resolver->resolve($ownerType, $ownerId, $slot));
    }
}

if (!function_exists('tile_defaults')) {
    /** Every platform tile URL, for a view or script that needs the whole map. */
    function tile_defaults(): array
    {
        return app(\App\Services\TileMediaResolver::class)->defaults();
    }
}
