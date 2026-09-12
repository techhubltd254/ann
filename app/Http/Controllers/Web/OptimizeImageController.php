<?php

namespace App\Http\Controllers\Web;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Routing\Controller;

/**
 * Layer 2: Dynamic Image Optimizer (primary — origin side).
 *
 * GET /api/optimize-image?url=...&w=...&q=...
 * Fetches the source image, resizes to the requested width (gd), transcodes to
 * WebP and caches the result in R2 with immutable cache headers so each image
 * is processed exactly once, then served from the CDN forever.
 */
class OptimizeImageController extends Controller
{
    public function __invoke(Request $request)
    {
        $url = (string) $request->get('url');
        $width = min(2400, max(32, (int) ($request->get('w') ?? 640)));
        $quality = min(95, max(30, (int) ($request->get('q') ?? 75)));

        if (!$url || !Str::startsWith($url, ['http://', 'https://'])) {
            return response('Missing or invalid url parameter', 400);
        }

        // Allowlist hosts (R2 CDN + our own)
        $host = parse_url($url, PHP_URL_HOST) ?? '';
        $allowed = ['kicc-r2-media.techhubltd254.workers.dev', 'kicc-proxy.techhubltd254.workers.dev', 'media.kicctest.org', 'kicctest.org', 'origin.kicctest.org'];
        if (!in_array($host, $allowed, true)) {
            return response('Host not allowed', 403);
        }

        $cacheKey = 'img/' . md5($url . '|' . $width . '|' . $quality) . '.webp';
        $r2 = Storage::disk('r2');

        // Serve cached result if present
        if ($r2->exists($cacheKey)) {
            return response($r2->get($cacheKey), 200, [
                'Content-Type' => 'image/webp',
                'Cache-Control' => 'public, max-age=31536000, immutable',
                'X-Image-Cache' => 'HIT',
            ]);
        }

        // Fetch source
        $resp = null;
        try {
            $resp = \Illuminate\Support\Facades\Http::timeout(15)->withHeaders([
                'User-Agent' => 'kicc-image-optimizer/1.0',
            ])->get($url);
        } catch (\Throwable $e) {
            Log::warning("optimize-image fetch failed: {$e->getMessage()}");
        }

        if (!$resp || !$resp->ok()) {
            return response('Source fetch failed', 502);
        }

        $temp = tempnam(sys_get_temp_dir(), 'opti_') . '.img';
        file_put_contents($temp, $resp->body());

        $info = @getimagesize($temp);
        if (!$info) {
            @unlink($temp);
            return response('Unsupported image', 422);
        }
        [$srcW, $srcH] = $info;
        $mime = $info['mime'];

        $srcImg = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($temp),
            'image/png' => @imagecreatefrompng($temp),
            'image/gif' => @imagecreatefromgif($temp),
            'image/webp' => @imagecreatefromwebp($temp),
            default => null,
        };
        @unlink($temp);

        if (!$srcImg || !function_exists('imagewebp')) {
            return response('Processing unavailable', 500);
        }

        $dstW = min($width, $srcW);
        $dstH = (int) round($srcH * ($dstW / $srcW));
        $dstImg = imagecreatetruecolor($dstW, $dstH);
        imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);
        imagedestroy($srcImg);

        $outTemp = tempnam(sys_get_temp_dir(), 'optw_') . '.webp';
        imagewebp($dstImg, $outTemp, $quality);
        imagedestroy($dstImg);

        $bytes = (string) file_get_contents($outTemp);
        @unlink($outTemp);

        // Cache in R2 (immutable — keyed by url+width+quality)
        $r2->put($cacheKey, $bytes, ['visibility' => 'public']);

        return response($bytes, 200, [
            'Content-Type' => 'image/webp',
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'X-Image-Cache' => 'MISS',
        ]);
    }
}