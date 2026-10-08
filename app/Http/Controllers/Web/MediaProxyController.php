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

        // Hand the bytes to R2 itself. The bucket serves them natively with
        // Range support, so the 141-169 MB county films never pass through
        // PHP memory (a 128M limit) and seeking works without buffering.
        // A one-hour signed URL keeps the object private.
        try {
            $url = $disk->temporaryUrl($key, now()->addHour());
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('media-proxy signed url failed', ['key' => $key, 'msg' => $e->getMessage()]);
            abort(502, 'Media source unavailable');
        }

        return redirect()->away($url, 302, [
            'Cache-Control'                 => 'private, max-age=3600',
            'Access-Control-Allow-Origin'   => '*',
            'Access-Control-Expose-Headers' => 'Content-Type, Content-Length, Content-Range, Accept-Ranges',
        ]);
    }
}
