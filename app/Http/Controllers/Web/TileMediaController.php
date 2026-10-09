<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\CountyInstitution;
use App\Models\MediaAsset;
use App\Models\Venue;
use App\Models\Marketplace\Product;
use App\Services\AdminHierarchyScope;
use App\Services\TileMediaResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Universal tile media control.
 *
 * Every tile on the public site — landing hero, county, institution, product,
 * venue, brand mark, editorial plate — resolves through media_assets. This
 * controller is the only write path, so an upload / replace / delete here is
 * exactly what the site renders on the next request.
 *
 * Two scopes:
 *   · institution-scoped  /institution-admin/{slug}/tile-media   (their own tiles)
 *   · KICC-global         /kicc-admin/tile-media                 (platform tiles)
 */
class TileMediaController extends Controller
{
    /** 90 MiB — under the 100 MB edge request limit; larger files go via presigned R2. */
    private const MAX_KB = 92160;

    /** Response caches the public site reads. Dropped on every successful write. */
    private const PUBLIC_KEYS = ['reference.native.v1'];

    /* ─────────────────────────── guards ─────────────────────────── */

    private function actor(Request $r): \App\Models\User
    {
        $u = $r->user();
        abort_unless(
            $u && ($u->status ?? 'active') === 'active' && app(AdminHierarchyScope::class)->level($u),
            403,
            'An active assigned administration role is required.'
        );

        return $u;
    }

    private function scope(): AdminHierarchyScope
    {
        return app(AdminHierarchyScope::class);
    }

    /** Resolve the {institution} segment by slug or id — never implicit binding. */
    private function institution(string $key): CountyInstitution
    {
        $i = CountyInstitution::where('slug', $key)->orWhere('id', is_numeric($key) ? (int) $key : 0)->first();
        abort_unless($i, 404, 'Institution not found.');

        return $i;
    }

    private function guardInstitution(Request $r, CountyInstitution $institution): void
    {
        abort_unless(
            $this->scope()->canInstitution($this->actor($r), $institution),
            403,
            'This institution is outside your administration scope.'
        );
    }

    private function guardGlobal(Request $r): void
    {
        abort_unless(
            $this->scope()->global($this->actor($r)),
            403,
            'Only KICC-level administration may change platform tiles.'
        );
    }

    /* ─────────────────────────── targeting ─────────────────────────── */

    /** Map a public tile type onto its polymorphic owner. */
    private function target(string $type, string $id, ?CountyInstitution $institution): array
    {
        return match ($type) {
            'institution' => [CountyInstitution::class, (int) ($institution?->id ?? $id)],
            'product'     => [Product::class, (int) $id],
            'county'      => [County::class, (int) ($institution?->county_id ?? $id)],
            'venue'       => [Venue::class, (int) $id],
            'landing'     => ['landing_page', 1],
            'default'     => [TileMediaResolver::OWNER_TYPE, TileMediaResolver::OWNER_ID],
            default       => abort(422, 'Unknown tile type.'),
        };
    }

    /** Nothing is written against an entity the caller does not own. */
    private function guardTarget(string $type, string $id, ?CountyInstitution $institution, Request $r): void
    {
        switch ($type) {
            case 'product':
                $p = Product::find((int) $id);
                abort_unless($p, 404, 'Product not found.');
                if ($institution) {
                    abort_unless((int) $p->institution_id === (int) $institution->id, 403, 'That product belongs to a different institution.');
                } else {
                    $this->guardGlobal($r);
                }
                break;

            case 'venue':
                abort_unless(Venue::find((int) $id), 404, 'Venue not found.');
                if (! $institution) {
                    $this->guardGlobal($r);
                }
                break;

            case 'institution':
                if ($institution) {
                    $this->guardInstitution($r, $institution);
                } else {
                    $this->guardGlobal($r);
                }
                break;

            case 'county':
                if (! $institution) {
                    $this->guardGlobal($r);
                }
                break;

            case 'landing':
            case 'default':
                $this->guardGlobal($r);
                break;
        }
    }

    /** May this actor replace/delete this particular asset? */
    private function guardAsset(Request $r, MediaAsset $asset, ?CountyInstitution $institution): void
    {
        if ($asset->owner_type === TileMediaResolver::OWNER_TYPE || $asset->owner_type === 'landing_page') {
            $this->guardGlobal($r);

            return;
        }

        if ($institution) {
            $this->guardInstitution($r, $institution);
            $owns = ((int) $asset->owner_id === (int) $institution->id && $asset->owner_type === CountyInstitution::class)
                || ($asset->owner_type === County::class && (int) $asset->owner_id === (int) $institution->county_id)
                || ($asset->owner_type === Product::class && Product::where('id', $asset->owner_id)->where('institution_id', $institution->id)->exists());

            abort_unless($owns, 403, 'That media does not belong to this institution.');

            return;
        }

        $this->guardGlobal($r);
    }

    /* ─────────────────────────── writes ─────────────────────────── */

    private function forgetPublic(): void
    {
        foreach (self::PUBLIC_KEYS as $k) {
            Cache::forget($k);
        }
        try {
            app(\App\Services\CacheSyncService::class)->kicc();
        } catch (\Throwable $e) {
            // Best-effort: the write already committed.
        }
    }

    private function kindOf(string $mime): string
    {
        return str_starts_with($mime, 'video/') ? 'video' : 'image';
    }

    /** Remove the DB row and the bytes it owns. Never touches a peer's file. */
    private function retire(MediaAsset $asset): void
    {
        $paths = array_unique(array_filter([
            $asset->path,
            ...$asset->derivatives->pluck('path')->all(),
        ]));

        foreach ($paths as $p) {
            if (str_starts_with((string) $p, 'http')) {
                continue;
            }
            try {
                Storage::disk($asset->disk ?: 'public')->delete($p);
            } catch (\Throwable $e) {
                // A missing object must not block deleting the record.
            }
        }

        $asset->derivatives()->delete();
        $asset->delete();
    }

    /** Write the upload and return the new asset. One code path for both kinds. */
    private function persist(Request $r, string $ownerType, int $ownerId, string $slot, array $extra = []): MediaAsset
    {
        $file = $r->file('file');
        $mime = (string) ($file->getMimeType() ?: '');
        $kind = $this->kindOf($mime);

        $bucket = $ownerType === TileMediaResolver::OWNER_TYPE
            ? 'tiles/platform'
            : 'tiles/' . Str::slug(class_basename($ownerType));
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin');
        $path = $bucket . '/' . $ownerId . '/' . $slot . '-' . Str::uuid() . '.' . $ext;

        $disk = 'public';
        $stream = fopen($file->getRealPath(), 'rb');
        try {
            $ok = Storage::disk($disk)->writeStream($path, $stream, ['visibility' => 'public']);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        abort_unless($ok && Storage::disk($disk)->exists($path), 503, 'The file could not be stored. No tile record was published.');

        // Replace semantics: a tile keeps exactly one active asset per slot.
        MediaAsset::query()
            ->where('owner_type', $ownerType)
            ->where('owner_id', $ownerId)
            ->where('slot', $slot)
            ->get()
            ->each(fn (MediaAsset $old) => $this->retire($old));

        $asset = MediaAsset::create([
            'uuid' => (string) Str::uuid(),
            'owner_type' => $ownerType,
            'owner_id' => $ownerId,
            'slot' => $slot,
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $mime ?: 'application/octet-stream',
            'kind' => $kind,
            'size_bytes' => (int) $file->getSize(),
            'status' => 'ready',
            'alt_text' => $r->string('alt_text')->toString() ?: null,
            'metadata' => array_merge([
                'source' => 'tile-admin',
                'uploaded_by' => $r->user()?->id,
            ], $extra),
        ]);

        $asset->derivatives()->create([
            'kind' => $kind,
            'path' => $path,
            'mime' => $asset->mime,
            'size_bytes' => $asset->size_bytes,
            'variant' => 'source',
        ]);

        return $asset;
    }

    private function validateUpload(Request $r): void
    {
        $r->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,webp,avif,gif,svg,mp4,webm,mov|max:' . self::MAX_KB,
            'slot' => 'required|string|max:32',
            'tile_type' => 'required|in:institution,product,county,venue,landing,default',
            'tile_id' => 'nullable|string|max:32',
        ]);
    }

    private function respond(Request $r, array $payload, int $code = 200)
    {
        if ($r->expectsJson() || $r->ajax()) {
            return response()->json($payload, $code)->header('Cache-Control', 'private,no-store');
        }

        return back()->with(($payload['ok'] ?? false) ? 'success' : 'error', $payload['message'] ?? 'Tile media updated.');
    }

    /* ─────────────────────────── endpoints ─────────────────────────── */

    /** Every slot for this owner, plus the default behind it. */
    public function index(Request $r, TileMediaResolver $resolver, ?string $institution = null)
    {
        if ($institution) {
            $inst = $this->institution($institution);
            $this->guardInstitution($r, $inst);
            $ownerType = CountyInstitution::class;
            $ownerId = (int) $inst->id;
        } else {
            $this->guardGlobal($r);
            $ownerType = TileMediaResolver::OWNER_TYPE;
            $ownerId = TileMediaResolver::OWNER_ID;
        }

        return response()->json([
            'owner' => ['type' => $ownerType, 'id' => $ownerId],
            'slots' => $resolver->slotsFor($ownerType, $ownerId),
            'labels' => $resolver->labels(),
            'defaults' => $resolver->defaults(),
            'source' => 'media_assets (polymorphic)',
        ])->header('Cache-Control', 'private,no-store');
    }

    /** Upload (or replace) the media for one tile slot. */
    public function store(Request $r, TileMediaResolver $resolver, ?string $institution = null)
    {
        $this->validateUpload($r);

        $inst = $institution ? $this->institution($institution) : null;
        if ($inst) {
            $this->guardInstitution($r, $inst);
        }

        $type = $r->string('tile_type')->toString();
        $id = $r->string('tile_id')->toString();
        $this->guardTarget($type, $id, $inst, $r);

        [$ownerType, $ownerId] = $this->target($type, $id, $inst);
        $slot = $r->string('slot')->toString();

        $asset = $this->persist($r, $ownerType, $ownerId, $slot, [
            'tile_type' => $type,
            'tile_id' => $id ?: null,
        ]);
        $this->forgetPublic();

        return $this->respond($r, [
            'ok' => true,
            'message' => 'Tile media uploaded and live.',
            'id' => $asset->id,
            'slot' => $slot,
            'url' => $resolver->url($asset),
            'tile' => $resolver->tile($ownerType, $ownerId, $slot),
            'slots' => $resolver->slotsFor($ownerType, $ownerId),
        ], 201);
    }

    /** Replace the bytes of an existing tile asset, keeping its slot. */
    public function replace(Request $r, MediaAsset $asset, TileMediaResolver $resolver, ?string $institution = null)
    {
        $inst = $institution ? $this->institution($institution) : null;
        $this->guardAsset($r, $asset, $inst);

        $r->validate(['file' => 'required|file|mimes:jpg,jpeg,png,webp,avif,gif,svg,mp4,webm,mov|max:' . self::MAX_KB]);

        $slot = (string) $asset->slot;
        $ownerType = (string) $asset->owner_type;
        $ownerId = (int) $asset->owner_id;

        $new = DB::transaction(fn () => $this->persist($r, $ownerType, $ownerId, $slot));
        $this->forgetPublic();

        return $this->respond($r, [
            'ok' => true,
            'message' => 'Tile media replaced.',
            'id' => $new->id,
            'slot' => $slot,
            'url' => $resolver->url($new),
            'tile' => $resolver->tile($ownerType, $ownerId, $slot),
            'slots' => $resolver->slotsFor($ownerType, $ownerId),
        ]);
    }

    /** Delete one tile asset and its bytes. */
    public function destroy(Request $r, MediaAsset $asset, TileMediaResolver $resolver, ?string $institution = null)
    {
        $inst = $institution ? $this->institution($institution) : null;
        $this->guardAsset($r, $asset, $inst);

        $slot = (string) $asset->slot;
        $ownerType = (string) $asset->owner_type;
        $ownerId = (int) $asset->owner_id;

        DB::transaction(fn () => $this->retire($asset));
        $this->forgetPublic();

        return $this->respond($r, [
            'ok' => true,
            'message' => 'Tile media deleted. The default (or placeholder) shows again.',
            'slot' => $slot,
            'tile' => $resolver->tile($ownerType, $ownerId, $slot),
            'slots' => $resolver->slotsFor($ownerType, $ownerId),
        ]);
    }

    /** Drop an override so the tile falls back to its platform default. */
    public function reset(Request $r, TileMediaResolver $resolver, ?string $institution = null)
    {
        $r->validate([
            'slot' => 'required|string|max:32',
            'tile_type' => 'required|in:institution,product,county,venue,landing,default',
            'tile_id' => 'nullable|string|max:32',
        ]);

        $inst = $institution ? $this->institution($institution) : null;
        if ($inst) {
            $this->guardInstitution($r, $inst);
        }

        $type = $r->string('tile_type')->toString();
        $id = $r->string('tile_id')->toString();
        $this->guardTarget($type, $id, $inst, $r);

        [$ownerType, $ownerId] = $this->target($type, $id, $inst);
        $slot = $r->string('slot')->toString();

        DB::transaction(function () use ($ownerType, $ownerId, $slot) {
            MediaAsset::query()
                ->where('owner_type', $ownerType)
                ->where('owner_id', $ownerId)
                ->where('slot', $slot)
                ->get()
                ->each(fn (MediaAsset $a) => $this->retire($a));
        });
        $this->forgetPublic();

        return $this->respond($r, [
            'ok' => true,
            'message' => 'Tile reset to its default.',
            'slot' => $slot,
            'tile' => $resolver->tile($ownerType, $ownerId, $slot),
            'slots' => $resolver->slotsFor($ownerType, $ownerId),
        ]);
    }
}
