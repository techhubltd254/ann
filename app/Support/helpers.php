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
        return rtrim(config('media.cdn_url', url('storage')), '/');
    }
}

if (!function_exists('img_url')) {
    function img_url(?string $path, int $width = 0, string $format = 'auto'): string
    {
        if (!$path) return '';

        $storage = rtrim(config('media.cdn_url', url('storage')), '/');

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

        $storage = rtrim(config('media.cdn_url', url('storage')), '/');
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
        $storage = rtrim(config('media.cdn_url', url('storage')), '/');

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
            $v = \App\Models\ImageVariant::where('source_hash', md5($sourceUrl))->latest('id')->first();
            return $v?->blur;
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
