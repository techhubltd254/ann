<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaProxyController extends Controller
{
    public function video(string $path, Request $request)
    {
        $key = preg_replace('#^storage/#', '', $path);
        return $this->serve($key, $request);
    }

    public function derivative(string $path, Request $request)
    {
        return $this->serve($path, $request);
    }

    private function serve(string $key, Request $request)
    {
        try {
            $disk = Storage::disk('r2');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('media-proxy r2 disk error', ['msg' => $e->getMessage()]);
            abort(500, 'Storage unavailable');
        }

        if (!$disk->exists($key)) {
            abort(404);
        }

        $mime = $disk->mimeType($key) ?: 'application/octet-stream';
        $size = (int) $disk->size($key);

        // Byte range requested by the browser (video scrubbing / seeking)
        $start = 0;
        $end   = max($size - 1, 0);
        $partial = false;
        if ($range = $request->header('Range')) {
            if (preg_match('/bytes=(\d+)-(\d*)/', $range, $m)) {
                $start = (int) $m[1];
                $end   = $m[2] !== '' ? min((int) $m[2], $size - 1) : $size - 1;
                $partial = true;
            }
        }
        if ($start > $end) {
            return response('', 416, ['Content-Range' => "bytes */{$size}"]);
        }
        $length = $end - $start + 1;

        $headers = [
            'Content-Type'                  => $mime,
            'Accept-Ranges'                 => 'bytes',
            'Access-Control-Allow-Origin'   => '*',
            'Access-Control-Expose-Headers' => 'Content-Type, Content-Length, Content-Range, Accept-Ranges',
            'Cache-Control'                 => 'public, max-age=86400, immutable',
        ];

        // One ranged GET straight from R2; the body is streamed, never buffered.
        try {
            $client = $disk->getClient();
            $args = [
                'Bucket' => config('filesystems.disks.r2.bucket'),
                'Key'    => $key,
            ];
            if ($partial) {
                $args['Range'] = "bytes={$start}-{$end}";
            }
            $result = $client->getObject($args);
            $body   = $result['Body'];
        } catch (\Throwable $e) {
            // Streaming the SDK body is an optimisation only. If it is not
            // available (no getClient(), SDK/credential quirk, or a body that
            // cannot be read), hand the browser a short-lived signed R2 URL
            // instead: R2 then serves the bytes itself, natively ranged.
            \Illuminate\Support\Facades\Log::warning('media-proxy: falling back to signed R2 redirect', [
                'key' => $key,
                'msg' => $e->getMessage(),
            ]);
            try {
                return redirect()->away($disk->temporaryUrl($key, now()->addHour()));
            } catch (\Throwable $e2) {
                \Illuminate\Support\Facades\Log::error('media-proxy: signed URL failed too', ['key' => $key, 'msg' => $e2->getMessage()]);
                abort(502, 'Media source unavailable');
            }
        }

        if ($partial) {
            $headers['Content-Range']  = "bytes {$start}-{$end}/{$size}";
            $headers['Content-Length'] = $length;
            $status = 206;
        } else {
            $headers['Content-Length'] = $size;
            $status = 200;
        }

        return new \Symfony\Component\HttpFoundation\StreamedResponse(function () use ($body, $length) {
            $sent = 0;
            while (!feof($body) && $sent < $length) {
                $chunk = $body->read(min(262144, $length - $sent));
                if ($chunk === '' || $chunk === false) {
                    break;
                }
                echo $chunk;
                $sent += strlen($chunk);
                if (ob_get_level() > 0) {
                    @ob_flush();
                }
                flush();
            }
        }, $status, $headers);
    }
}
