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
        $this->retireSlot($venue, $slot);

        $asset = app(MediaLibraryService::class)->store($file, [
            'disk' => $disk,
            'directory' => $directory,
            'owner_type' => self::OWNER_TYPE,
            'owner_id' => $venue->id,
            'slot' => $slot,
            'alt_text' => $venue->name . ' — ' . $slot,
        ]);

        // store() writes status "uploaded"; a tile only binds on "ready".
        $asset->forceFill(['status' => 'ready'])->save();

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
            'conference_rate' => 'nullable|numeric|min:0',
            'exhibition_rate' => 'nullable|numeric|min:0',
            'concert_rate' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');

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
        $this->guard();

        $data = $request->validate([
            'venue_id' => 'required|integer',
            'slot' => 'required|in:cover,hero_video',
            'mime' => 'required|string|max:100',
            'original_name' => 'required|string|max:255',
        ]);

        $venue = Venue::findOrFail($data['venue_id']);

        $ext = strtolower(pathinfo($data['original_name'], PATHINFO_EXTENSION) ?: 'bin');
        $sub = $data['slot'] === 'cover' ? 'cover' : 'hero';
        $path = 'venues/' . $venue->slug . '/' . $sub . '/' . Str::uuid() . '.' . $ext;

        $result = app(R2PresignedUploadService::class)
            ->generateUploadPresignedUrl($path, $data['mime']);

        return response()->json($result + ['path' => $path, 'slot' => $data['slot']]);
    }

    public function confirmR2Upload(Request $request)
    {
        $this->guard();

        $data = $request->validate([
            'venue_id' => 'required|integer',
            'slot' => 'required|in:cover,hero_video',
            'path' => 'required|string|max:500',
            'mime' => 'required|string|max:100',
            'size_bytes' => 'required|integer|min:1',
            'original_name' => 'nullable|string|max:255',
        ]);

        $venue = Venue::findOrFail($data['venue_id']);
        $isVideo = str_contains($data['mime'], 'video');

        $this->retireSlot($venue, $data['slot']);

        $asset = MediaAsset::create([
            'uuid' => (string) Str::uuid(),
            'owner_id' => $venue->id,
            'owner_type' => self::OWNER_TYPE,
            'slot' => $data['slot'],
            'disk' => 'r2',
            'path' => $data['path'],
            'original_name' => $data['original_name'] ?? basename($data['path']),
            'mime' => $data['mime'],
            'kind' => $isVideo ? 'video' : 'image',
            'size_bytes' => $data['size_bytes'],
            'status' => 'ready',
            'alt_text' => $venue->name . ' — ' . $data['slot'],
            'uploadedByUserId' => auth()->id(),
        ]);

        MediaDerivative::create([
            'media_asset_id' => $asset->id,
            'kind' => $isVideo ? 'video_mp4' : 'original',
            'path' => $data['path'],
            'mime' => $data['mime'],
            'size_bytes' => $data['size_bytes'],
            'variant' => $isVideo ? '1080p' : 'source',
        ]);

        $venue->forceFill(
            $data['slot'] === 'cover'
                ? ['cover_image' => $data['path']]
                : ['hero_video_url' => $data['path']]
        )->save();

        $this->forgetPublic();

        return response()->json(['asset_id' => $asset->id, 'path' => $data['path']]);
    }
}
