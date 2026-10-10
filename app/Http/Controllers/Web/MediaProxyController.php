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
        abort_if(str_contains($key, "\0") || preg_match('~(?:^|/)\.\.(?:/|$)~', $key), 404);
        $asset = \App\Models\MediaAsset::where('path', $key)->first();
        if (!$asset) {
            $derivative = \App\Models\MediaDerivative::where('path', $key)->first();
            if ($derivative) $asset = \App\Models\MediaAsset::find($derivative->media_asset_id);
        }
        if ($asset && $asset->kind === 'model') {
            return app(PublicModelController::class)->bytes($request, $asset);
        }
        if ($asset) {
            $user = $request->user();
            $scope = app(\App\Services\AdminHierarchyScope::class);
            $institution = match ($asset->owner_type) {
                \App\Models\CountyInstitution::class => \App\Models\CountyInstitution::find($asset->owner_id),
                \App\Models\Marketplace\Product::class => \App\Models\CountyInstitution::find(\App\Models\Marketplace\Product::find($asset->owner_id)?->institution_id),
                default => null,
            };
            $admin = $user && ($user->status ?? 'active') === 'active' &&
                ($institution ? $scope->canInstitution($user, $institution) : $scope->global($user));
            $public = $asset->status === 'ready' && !str_starts_with($asset->slot ?? '', 'draft__') &&
                !str_starts_with($asset->slot ?? '', 'archived__') && ($asset->metadata['publication'] ?? '') !== 'draft';
            if (in_array($asset->owner_type, [\App\Models\CountyInstitution::class, \App\Models\Marketplace\Product::class], true)) {
                $public = $public && $institution?->is_published;
                if ($asset->owner_type === \App\Models\Marketplace\Product::class) {
                    $public = $public && \App\Models\Marketplace\Product::find($asset->owner_id)?->status === 'active';
                }
            }
            abort_unless($public || $admin, 404);
        } elseif (str_starts_with($key, 'models/') || str_starts_with($key, 'institutions/')) {
            // Unregistered institutional objects are not published catalogue media.
            abort(404);
        }
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
