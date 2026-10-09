<?php

namespace App\Services;

use App\Models\MediaAsset;
use App\Support\MediaAssetIndex;

/**
 * One resolver for every tile on the public site.
 *
 * Order of precedence — and nothing else:
 *   1. an admin upload bound to that exact tile   (media_assets row, status=ready)
 *   2. a tile-default row (the AI imagery / brand mark), also a media_assets row,
 *      so it can be replaced or deleted from the admin like any other asset
 *   3. null — the UI renders its own placeholder
 *
 * No media path is hardcoded in a view or a script. Every URL in the output came
 * from the database, which is what makes upload / replace / delete actually land.
 */
class TileMediaResolver
{
    /** Optional request-scoped index: turns one query per tile into none. */
    private ?MediaAssetIndex $index = null;

    public function withIndex(MediaAssetIndex $index): static
    {
        $this->index = $index;

        return $this;
    }

    /**
     * Every slot the public site can ask for. Each one is a row in media_assets
     * with owner_type = 'tile_default', so each one is editable from the admin.
     */
    public const SLOTS = [
        // Structural plates (posters behind the experience)
        'hero'       => ['Landing hero',            'video'],
        'city'       => ['City / skyline plate',    'image'],
        'land'       => ['Landscape plate',         'image'],
        'booth'      => ['Exhibition floor plate',  'image'],
        'hall'       => ['Hall interior plate',     'image'],
        'savanna'    => ['Savanna aerial plate',    'image'],

        // Brand mark
        'logo'       => ['KICC brand mark',         'image'],

        // Editorial chapter imagery
        'ed_savanna' => ['Editorial · savanna',     'image'],
        'ed_nairobi' => ['Editorial · Nairobi',     'image'],
        'ed_skyline' => ['Editorial · skyline',     'image'],
        'ed_hall'    => ['Editorial · hall',        'image'],
        'ed_escarp'  => ['Editorial · escarpment',  'image'],
        'ed_mara'    => ['Editorial · Mara',        'image'],

        // Archive strip plates
        'plate_0'    => ['Archive plate 01 · hall',        'image'],
        'plate_1'    => ['Archive plate 02 · market',      'image'],
        'plate_2'    => ['Archive plate 03 · city',        'image'],
        'plate_3'    => ['Archive plate 04 · savanna',     'image'],
        'plate_4'    => ['Archive plate 05 · expo floor',  'image'],
        'plate_5'    => ['Archive plate 06 · city (alt)',  'image'],

        // Per-entity fallbacks used when a record has no upload of its own
        'product'    => ['Product fallback',        'image'],
        'venue'      => ['Venue fallback',          'image'],
        'county'     => ['County fallback',         'image'],
        'institution'=> ['Institution fallback',    'image'],
        'video_hero' => ['Video hero fallback',     'video'],
    ];

    /** Slots the public JS reads by name. */
    public const DEFAULT_SLOTS = [
        'hero', 'city', 'land', 'booth', 'hall', 'savanna',
        'logo', 'product', 'venue', 'county', 'institution', 'video_hero',
        'ed_savanna', 'ed_nairobi', 'ed_skyline', 'ed_hall', 'ed_escarp', 'ed_mara',
        'plate_0', 'plate_1', 'plate_2', 'plate_3', 'plate_4', 'plate_5',
    ];

    public const OWNER_TYPE = 'tile_default';
    public const SEED_TYPE  = 'tile_default_seed';
    public const OWNER_ID   = 1;

    /**
     * Newest ready row for one (owner, slot).
     *
     * Uses the primed index when it covers this owner — identical result
     * (`latest('id')->first()` === last of an ascending-id set) without a
     * per-tile round trip to TiDB. Falls back to the original query otherwise.
     */
    private function pick(string $ownerType, int $ownerId, string $slot): ?MediaAsset
    {
        if ($this->index && $this->index->isPrimed($ownerType, $ownerId)) {
            return $this->index->forSlot($ownerType, $ownerId, $slot);
        }

        return MediaAsset::query()
            ->where('owner_type', $ownerType)
            ->where('owner_id', $ownerId)
            ->where('slot', $slot)
            ->where('status', 'ready')
            ->latest('id')
            ->first();
    }

    /** Resolve the winning asset for one tile slot. */
    public function resolve(string $ownerType, int $ownerId, string $slot): ?MediaAsset
    {
        // 1. the entity's own upload
        if ($own = $this->pick($ownerType, $ownerId, $slot)) {
            return $own;
        }

        // 2. admin override on the platform tile (owner_type = tile_default)
        if ($override = $this->pick(self::OWNER_TYPE, self::OWNER_ID, $slot)) {
            return $override;
        }

        // 3. the shipped seed — immutable, so deleting an admin upload restores it
        //    instead of leaving the tile blank.
        return $this->pick(self::SEED_TYPE, self::OWNER_ID, $slot);
    }

    /** Turn a resolved asset into the URL the browser should fetch. */
    public function url(?MediaAsset $asset): ?string
    {
        if (! $asset) {
            return null;
        }

        $path = (string) $asset->path;
        if ($path === '') {
            return null;
        }

        // Seeded defaults may point straight at an external URL.
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        // Cache-bust so a replaced file is never served from a stale browser cache.
        $v = $asset->updated_at?->timestamp ?? $asset->id;

        if (($asset->disk ?? 'public') === 'r2') {
            return url('/media/video/' . ltrim($path, '/')) . '?v=' . $v;
        }

        return url('/storage/' . ltrim($path, '/')) . '?v=' . $v;
    }

    /**
     * The shape the public JS consumes for one tile.
     * `state=published` means render it; `state=empty` means show the placeholder.
     */
    public function tile(string $ownerType, int $ownerId, string $slot): array
    {
        $asset = $this->resolve($ownerType, $ownerId, $slot);
        $url = $this->url($asset);

        if (! $asset || ! $url) {
            return ['state' => 'empty', 'slot' => $slot, 'url' => null];
        }

        return [
            'state' => 'published',
            'slot' => $slot,
            'kind' => $asset->kind ?: 'image',
            'url' => $url,
            'poster' => null,
            'alt' => $asset->alt_text ?: $asset->original_name,
            'assetId' => (string) $asset->id,
            'source' => in_array($asset->owner_type, [self::OWNER_TYPE, self::SEED_TYPE], true) ? 'ai-default' : 'admin-upload',
        ];
    }

    /** Every tile-default URL, so the public JS never carries a literal URL. */
    public function defaults(): array
    {
        $out = [];
        foreach (self::DEFAULT_SLOTS as $slot) {
            $out[$slot] = $this->url($this->resolve(self::OWNER_TYPE, self::OWNER_ID, $slot));
        }

        return $out;
    }

    /** Just the URL for a default slot — the Blade-side shorthand. */
    public function defaultUrl(string $slot): ?string
    {
        return $this->url($this->resolve(self::OWNER_TYPE, self::OWNER_ID, $slot));
    }

    /** One row per slot, for the admin list: winner, source, and the default behind it. */
    public function slotsFor(string $ownerType, int $ownerId): array
    {
        $own = MediaAsset::query()
            ->where('owner_type', $ownerType)
            ->where('owner_id', $ownerId)
            ->whereIn('kind', ['image', 'video'])
            ->latest('id')
            ->get()
            ->keyBy('slot');

        $defaults = MediaAsset::query()
            ->whereIn('owner_type', [self::OWNER_TYPE, self::SEED_TYPE])
            ->where('owner_id', self::OWNER_ID)
            ->whereIn('kind', ['image', 'video'])
            ->orderBy('id')
            ->get()
            ->keyBy('slot');

        $rows = [];
        foreach (self::SLOTS as $slot => [$label, $kind]) {
            $ownAsset = $own->get($slot);
            $defAsset = $defaults->get($slot);
            $winner = $ownAsset ?: $defAsset;
            $rows[] = [
                'slot' => $slot,
                'label' => $label,
                'kind' => $winner?->kind ?: $kind,
                'url' => $this->url($winner),
                'state' => $winner ? 'published' : 'empty',
                'source' => $ownAsset ? 'admin-upload' : ($defAsset ? 'ai-default' : 'none'),
                'asset_id' => $ownAsset?->id,
                'default_asset_id' => $defAsset?->id,
                'default_url' => $this->url($defAsset),
                'alt' => $winner?->alt_text ?: $winner?->original_name,
                'updated_at' => (string) ($winner?->updated_at ?? ''),
            ];
        }

        return $rows;
    }
}
