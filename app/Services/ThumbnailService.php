<?php

namespace App\Services;

use App\Models\County;
use App\Models\CountyInstitution;
use App\Models\CountyTourismAttraction;
use App\Models\Marketplace\Product;
use App\Models\MediaAsset;
use App\Models\SectorEntity;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * ThumbnailService — guarantees a real, accurate thumbnail for any entity
 * when its image is missing. Priority chain:
 *   1. Explicit image on the entity
 *   2. Video poster / thumbnail derivative (from hero/4D videos)
 *   3. First video frame extracted server-side (ffmpeg)
 *   4. Fallback image per category/sector (county media library)
 *   5. Generated branded gradient placeholder (no external service)
 */
class ThumbnailService
{
    /** Category → fallback image slug used by the media library */
    protected static array $categoryFallbacks = [
        'nature' => 'nature',
        'adventure' => 'adventure',
        'agriculture' => 'agriculture',
        'culture' => 'culture',
        'wildlife' => 'wildlife',
        'beach' => 'beach',
        'historical' => 'historical',
        'waterfall' => 'waterfall',
        'hotel' => 'hotel',
        'resort' => 'resort',
        'guest house' => 'hotel',
        'conference' => 'conference',
        'restaurant' => 'restaurant',
        'tourism' => 'tourism',
        'tours' => 'tourism',
        'accommodation' => 'hotel',
        'food' => 'agriculture',
        'coffee' => 'agriculture',
        'tea' => 'agriculture',
        'marine' => 'tourism',
        'eco-tourism' => 'nature',
        'heritage' => 'historical',
        'museum' => 'historical',
        'leisure' => 'nature',
        'adventure' => 'adventure',
    ];

    /**
     * Resolve the best thumbnail URL for any model.
     *
     * @param object $entity  Model with image_url / video_url / category / name
     * @param string $countySlug
     */
    public static function for($entity, string $countySlug = ''): ?string
    {
        $cacheKey = 'thumb_' . get_class($entity) . '_' . ($entity->id ?? spl_object_id($entity));
        return Cache::remember($cacheKey, 86400, function () use ($entity, $countySlug) {
        $countySlug = $countySlug ?: optional($entity->county ?? null)?->slug ?? '';

        // 1. Explicit image on the entity
        $image = $entity->image_url
            ?? $entity->cover_image_url
            ?? $entity->logo_url
            ?? ($entity->images?->first()?->url ?? null);
        if ($image) return $image;

        // 2. Product variant image
        if ($entity instanceof Product && $entity->relationLoaded('variants')) {
            $variantImg = $entity->variants->first()?->image_url;
            if ($variantImg) return $variantImg;
        }

        // 3. Video poster / thumbnail derivative
        $videoUrl = $entity->video_url ?? null;
        if ($videoUrl) {
            $asset = MediaAsset::where('path', 'like', '%' . basename($videoUrl) . '%')->first();
            if ($asset) {
                $poster = $asset->posterUrl() ?? $asset->thumbnailUrl();
                if ($poster) return $poster;
            }
        }

        // 4. Institution hero video poster
        if ($entity instanceof CountyInstitution) {
            $hero = MediaAsset::resolveSlot(CountyInstitution::class, $entity->id, 'hero_video');
            if ($hero) {
                $poster = $hero->posterUrl() ?? $hero->thumbnailUrl();
                if ($poster) return $poster;
            }
        }

        // 5. Sector entity 4D video poster
        if ($entity instanceof SectorEntity) {
            $video = MediaAsset::where('owner_type', SectorEntity::class)
                ->where('owner_id', $entity->id)->where('slot', '4d_video')->first();
            if ($video) {
                $poster = $video->posterUrl() ?? $video->thumbnailUrl();
                if ($poster) return $poster;
            }
        }

        // 6. County fallback by category
        if ($countySlug) {
            $category = strtolower($entity->category ?? $entity->type ?? 'default');
            $slug = self::$categoryFallbacks[$category] ?? self::$categoryFallbacks[explode(' ', $category)[0]] ?? null;
            if ($slug) {
                $candidate = media("counties/{$countySlug}/{$slug}.jpg");
                if (self::exists($candidate)) return $candidate;
            }
            // Generic county fallback
            $candidate = media("counties/{$countySlug}/hero.jpeg");
            if (self::exists($candidate)) return $candidate;
        }

        // 7. Branded gradient placeholder (always available)
        return static::placeholder($entity->name ?? 'KICC', $category ?? null);
        });
    }

    /** Generate a deterministic branded SVG placeholder (no external service). */
    public static function placeholder(string $name, ?string $category = null): string
    {
        $palettes = [
            ['#0b0b0b', '#1a3070'], ['#b3261e', '#b71c1c'], ['#0b0b0b', '#1a1a2e'],
            ['#046bd2', '#0EA5E9'], ['#2D6A4F', '#40916C'], ['#8B6914', '#FFCD05'],
        ];
        $hash = crc32($name);
        [$from, $to] = $palettes[$hash % count($palettes)];
        $initials = strtoupper(substr(trim($name), 0, 2) ?: 'KC');
        $cat = $category ? htmlspecialchars(ucfirst($category)) : 'KICC';

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="800" height="600">'
            . "<defs><linearGradient id=\"g\" x1=\"0\" y1=\"0\" x2=\"1\" y2=\"1\">"
            . "<stop offset=\"0\" stop-color=\"{$from}\"/><stop offset=\"1\" stop-color=\"{$to}\"/></linearGradient></defs>"
            . "<rect width=\"800\" height=\"600\" fill=\"url(#g)\"/>"
            . "<circle cx=\"650\" cy=\"80\" r=\"160\" fill=\"rgba(255,255,255,0.05)\"/>"
            . "<circle cx=\"100\" cy=\"520\" r=\"120\" fill=\"rgba(255,255,255,0.04)\"/>"
            . "<text x=\"400\" y=\"280\" text-anchor=\"middle\" font-family=\"Inter,sans-serif\" font-size=\"140\" font-weight=\"800\" fill=\"rgba(255,255,255,0.85)\">{$initials}</text>"
            . "<text x=\"400\" y=\"360\" text-anchor=\"middle\" font-family=\"Inter,sans-serif\" font-size=\"28\" font-weight=\"600\" fill=\"rgba(255,255,255,0.6)\">{$cat}</text>"
            . '</svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /** Best-effort remote existence check. */
    protected static function exists(string $url): bool
    {
        static $cache = [];
        if (isset($cache[$url])) return $cache[$url];
        try {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_NOBODY => true,
                CURLOPT_TIMEOUT => 3,
                CURLOPT_RETURNTRANSFER => true,
            ]);
            $code = 0;
            curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            return $cache[$url] = ($code >= 200 && $code < 400);
        } catch (\Throwable $e) {
            return $cache[$url] = false;
        }
    }

    /**
     * Generate thumbnail derivatives (poster/webp) for a video asset using
     * ffmpeg if available. Returns the poster URL or null.
     */
    public static function generateFromVideo(MediaAsset $asset): ?string
    {
        if ($asset->posterUrl()) return $asset->posterUrl();

        $ffmpeg = trim((string) shell_exec('which ffmpeg 2>/dev/null'));
        if (!$ffmpeg) return null;

        try {
            $disk = Storage::disk($asset->disk ?? 'r2');
            $tmp = tempnam(sys_get_temp_dir(), 'kicc_thumb_');
            $tmpJpg = str_replace('.tmp', '.jpg', $tmp);

            // Download video to temp
            $local = $tmp . '.mp4';
            $content = $disk->get($asset->path);
            if (!$content) return null;
            file_put_contents($local, $content);

            // Extract frame at 0.5s
            $cmd = sprintf(
                '%s -y -ss 0.5 -i %s -frames:v 1 -vf scale=800:-1 -q:v 3 %s 2>/dev/null',
                escapeshellarg($ffmpeg),
                escapeshellarg($local),
                escapeshellarg($tmpJpg)
            );
            shell_exec($cmd);
            if (!file_exists($tmpJpg)) return null;

            $posterPath = preg_replace('/\.(mp4|webm|mov)$/', '-poster.jpg', $asset->path);
            $disk->put($posterPath, file_get_contents($tmpJpg), 'public');

            // Create derivative record
            $asset->derivatives()->create([
                'kind' => 'poster',
                'path' => $posterPath,
                'mime' => 'image/jpeg',
                'size_bytes' => filesize($tmpJpg),
                'width' => 800,
                'height' => 600,
            ]);

            @unlink($local);
            @unlink($tmpJpg);
            @unlink($tmp);

            return $asset->posterUrl();
        } catch (\Throwable $e) {
            Log::warning('ThumbnailService ffmpeg failed: ' . $e->getMessage());
            return null;
        }
    }
}