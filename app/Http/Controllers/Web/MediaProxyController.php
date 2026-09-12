<?php

namespace App\Http\Controllers\Web;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaProxyController extends Controller
{
    public function video(string $path, Request $request)
    {
        \Illuminate\Support\Facades\Log::info('media-proxy video', ['path' => $path, 'url' => $request->fullUrl()]);
        $key = preg_replace('#^storage/#', '', $path);

        $disk = Storage::disk('r2');
        if (!$disk->exists($key)) {
            abort(404);
        }

        $mime = $disk->mimeType($key) ?? 'video/mp4';
        $size = $disk->size($key);

        $headers = [
            'Content-Type' => $mime,
            'Content-Length' => $size,
            'Accept-Ranges' => 'bytes',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Expose-Headers' => 'Content-Type, Content-Length, Content-Range, Accept-Ranges',
            'Cache-Control' => 'public, max-age=86400, immutable',
        ];

        $range = $request->header('Range');
        if ($range && preg_match('/bytes=(\d+)-(\d*)/', $range, $m)) {
            $start = (int) $m[1];
            $end = $m[2] !== '' ? (int) $m[2] : $size - 1;
            $headers['Content-Range'] = "bytes {$start}-{$end}/{$size}";
            $headers['Content-Length'] = $end - $start + 1;
            return response()->stream(function () use ($disk, $key, $start, $end) {
                $stream = $disk->readStream($key);
                if ($start > 0) fseek($stream, $start);
                $chunk = min(8192, $end - $start + 1);
                $sent = 0;
                while (!feof($stream) && $sent < ($end - $start + 1)) {
                    $data = fread($stream, $chunk);
                    $sent += strlen($data);
                    echo $data;
                }
                fclose($stream);
            }, 206, $headers);
        }

        return response($disk->get($key), 200, $headers);
    }

    public function derivative(string $path, Request $request)
    {
        \Illuminate\Support\Facades\Log::info('media-proxy derivative', ['path' => $path, 'url' => $request->fullUrl()]);
        $disk = Storage::disk('r2');
        if (!$disk->exists($path)) {
            abort(404);
        }

        $mime = $disk->mimeType($path) ?? 'application/octet-stream';
        $size = $disk->size($path);

        $headers = [
            'Content-Type' => $mime,
            'Content-Length' => $size,
            'Access-Control-Allow-Origin' => '*',
            'Cache-Control' => 'public, max-age=86400, immutable',
        ];

        return response($disk->get($path), 200, $headers);
    }
}