<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use App\Models\MediaDerivative;
use App\Models\Venue;
use App\Services\CacheSyncService;
use App\Services\MediaLibraryService;
use App\Services\R2PresignedUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * KICC venue management: full CRUD plus DB-backed media (cover image + hero video).
 *
 * Media is never hardcoded: every venue tile resolves through media_assets rows
 * bound to owner_type "venue" + owner_id + slot ("cover" | "hero_video"), so the
 * public site reflects an admin upload/replace/delete immediately.
 */
class VenueAdminController extends Controller
{
    /** Morph token used in media_assets.owner_type for venues. */
    protected const OWNER_TYPE = 'venue';

    /** 90 MiB — deliberately under the 100 MB Cloudflare/FPM edge limit. */
    protected const MAX_KB = 92160;

    protected const PUBLIC_KEYS = ['reference.native.v1'];

    // ─────────────────────────── guards ───────────────────────────

    protected function guard(): void
    {
        $user = auth()->user();
        if (! $user || ! $user->hasAnyRole(['kicc_admin', 'national_admin'])) {
            abort(403, 'Venue management requires a KICC or national administration role.');
        }
    }

    protected function forgetPublic(): void
    {
        foreach (self::PUBLIC_KEYS as $key) {
            Cache::forget($key);
        }
        try {
            app(CacheSyncService::class)->kicc();
        } catch (\Throwable $e) {
            Log::warning('venue cache sync failed: ' . $e->getMessage());
        }
    }

    // ─────────────────────────── media helpers ───────────────────────────

    protected function slotAsset(Venue $venue, string $slot): ?MediaAsset
    {
        return MediaAsset::query()
            ->where('owner_type', self::OWNER_TYPE)
            ->where('owner_id', $venue->id)
            ->where('slot', $slot)
            ->where('status', 'ready')
            ->latest('id')
            ->first();
    }

    protected function urlFor(?MediaAsset $asset): ?string
    {
        if (! $asset) {
            return null;
        }
        if ($asset->disk === 'r2' || $asset->disk === 'external') {
            return method_exists($asset, 'url') ? $asset->url() : media($asset->path);
        }

        return media($asset->path);
    }

    /** Retire any existing asset in this slot (DB row + R2/public bytes). */
    protected function retireSlot(Venue $venue, string $slot): void
    {
        $existing = MediaAsset::query()
            ->where('owner_type', self::OWNER_TYPE)
            ->where('owner_id', $venue->id)
            ->where('slot', $slot)
            ->get();

        foreach ($existing as $asset) {
            try {
                foreach ($asset->derivatives as $d) {
                    if ($d->path && ! str_starts_with($d->path, 'http')) {
                        Storage::disk($asset->disk)->delete($d->path);
                    }
                }
                if ($asset->path && ! str_starts_with($asset->path, 'http')) {
                    Storage::disk($asset->disk)->delete($asset->path);
                }
            } catch (\Throwable $e) {
                Log::warning('venue media retire failed: ' . $e->getMessage());
            }
            $asset->delete();
        }
    }

    protected function storeInto(Venue $venue, $file, string $slot, string $directory, string $disk): MediaAsset
    {
        // Keep the confirmed old upload until the new source has passed validation/preparation.
        $asset = app(MediaLibraryService::class)->store($file, [
            'disk' => $disk,
            'directory' => $directory,
            'owner_type' => self::OWNER_TYPE,
            'owner_id' => $venue->id,
            'slot' => $slot,
            'alt_text' => $venue->name . ' — ' . $slot,
        ]);

        // store() writes status "uploaded"; a tile only binds on "ready".
        $asset->forceFill(['status' => $asset->kind==='video'?'processing':'ready'])->save();
        if($asset->kind==='video')\App\Jobs\PrepareProductVideo::dispatch($asset->id,$asset->path);

        if ($asset->kind === 'video') {
            MediaDerivative::create([
                'media_asset_id' => $asset->id,
                'kind' => 'video_mp4',
                'path' => $asset->path,
                'mime' => $asset->mime,
                'size_bytes' => $asset->size_bytes,
                'variant' => '1080p',
            ]);
        } else {
            MediaDerivative::create([
                'media_asset_id' => $asset->id,
                'kind' => 'original',
                'path' => $asset->path,
                'mime' => $asset->mime,
                'size_bytes' => $asset->size_bytes,
                'variant' => 'source',
            ]);
        }

        return $asset;
    }

    // ─────────────────────────── pages ───────────────────────────

    public function index(Request $request)
    {
        $this->guard();

        $q = $request->get('q');

        $venues = Venue::query()
            ->when($q, fn ($query, $term) => $query->where(function ($w) use ($term) {
                $w->where('name', 'like', "%{$term}%")
                    ->orWhere('city', 'like', "%{$term}%")
                    ->orWhere('county', 'like', "%{$term}%");
            }))
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        $media = [];
        foreach ($venues as $v) {
            $cover = $this->slotAsset($v, 'cover');
            $video = $this->slotAsset($v, 'hero_video');
            $media[$v->id] = [
                'cover' => $this->urlFor($cover) ?: ($v->cover_image ? media($v->cover_image) : null),
                'cover_source' => $cover ? 'admin' : ($v->cover_image ? 'legacy' : null),
                'video' => $this->urlFor($video) ?: ($v->hero_video_url ?: null),
                'video_source' => $video ? 'admin' : ($v->hero_video_url ? 'legacy' : null),
                'video_kind' => $video?->kind,
            ];
        }

        return view('experience.pages.admin.venues.index', [
            'venues' => $venues,
            'filters' => ['q' => $q],
            'media' => $media,
        ]);
    }

    public function edit(int $id)
    {
        $this->guard();

        $venue = Venue::findOrFail($id);

        return view('experience.pages.admin.venues.edit', [
            'venue' => $venue,
            'cover' => $this->slotAsset($venue, 'cover'),
            'video' => $this->slotAsset($venue, 'hero_video'),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $this->guard();

        $venue = Venue::findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'venue_type' => 'nullable|string|max:60',
            'city' => 'nullable|string|max:120',
            'county' => 'nullable|string|max:120',
            'address' => 'nullable|string|max:255',
            'capacity' => 'nullable|integer|min:0|max:1000000',
            'description' => 'nullable|string|max:8000',
            'expected_video_description'=>'nullable|string|max:2000',
            'conference_rate' => 'nullable|numeric|min:0',
            'exhibition_rate' => 'nullable|numeric|min:0',
            'concert_rate' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $source=$venue->source_details??[];$source['expected_video_description']=$data['expected_video_description']??($source['expected_video_description']??null);unset($data['expected_video_description']);$data['source_details']=$source;
        $venue->fill($data)->save();

        $this->forgetPublic();

        return back()->with('success', 'Venue updated.');
    }

    // ─────────────────────────── media mutations ───────────────────────────

    public function uploadCover(Request $request, int $id)
    {
        $this->guard();

        $venue = Venue::findOrFail($id);

        $request->validate([
            'cover' => 'required|file|mimes:jpg,jpeg,png,webp,avif|max:' . self::MAX_KB,
        ]);

        $asset = $this->storeInto(
            $venue,
            $request->file('cover'),
            'cover',
            'venues/' . $venue->slug . '/cover',
            'public'
        );

        $venue->forceFill(['cover_image' => $asset->path])->save();
        $this->forgetPublic();

        return back()->with('success', 'Cover image updated (asset #' . $asset->id . ').');
    }

    public function uploadVideo(Request $request, int $id)
    {
        $this->guard();

        $venue = Venue::findOrFail($id);

        $request->validate([
            'video' => 'required|file|mimes:mp4,webm,mov|max:' . self::MAX_KB,
        ]);

        $asset = $this->storeInto(
            $venue,
            $request->file('video'),
            'hero_video',
            'venues/' . $venue->slug . '/hero',
            'r2'
        );

        $venue->forceFill(['hero_video_url' => $asset->path])->save();
        $this->forgetPublic();

        return back()->with('success', 'Hero video updated (asset #' . $asset->id . ').');
    }

    public function deleteVideo(int $id)
    {
        $this->guard();

        $venue = Venue::findOrFail($id);

        $this->retireSlot($venue, 'hero_video');
        $venue->forceFill(['hero_video_url' => null])->save();
        $this->forgetPublic();

        return back()->with('success', 'Hero video removed.');
    }

    public function deleteCover(int $id)
    {
        $this->guard();

        $venue = Venue::findOrFail($id);

        $this->retireSlot($venue, 'cover');
        $venue->forceFill(['cover_image' => null])->save();
        $this->forgetPublic();

        return back()->with('success', 'Cover image removed.');
    }

    // ─────────────────────────── direct-to-R2 (large files) ───────────────────────────

    public function r2PresignedUrl(Request $request)
    {
        $this->guard();abort(410,'Direct object confirmation is retired. Use /admin/uploads and select Venue.');
    }

    public function confirmR2Upload(Request $request)
    {
        $this->guard();abort(410,'Direct object confirmation is retired. Use /admin/uploads and select Venue.');
    }

}
