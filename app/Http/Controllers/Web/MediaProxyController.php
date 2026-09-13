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

        $mime = $disk->mimeType($key) ?? 'application/octet-stream';
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
            $stream = $disk->readStream($key);
            if ($start > 0) fseek($stream, $start);
            $data = stream_get_contents($stream, $end - $start + 1);
            fclose($stream);
            return response($data, 206, $headers);
        }

        return response($disk->get($key), 200, $headers);
    }
}