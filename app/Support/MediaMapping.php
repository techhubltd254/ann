<?php

namespace App\Support;

use App\Models\County;
use App\Models\CountyInstitution;
use App\Models\MediaAsset;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Strict video-to-entity mapping.
 *
 * Rule: an entity may only render footage whose R2 key is provably about that
 * entity — the key must carry the entity's own slug (or its id for entities
 * with no slug). A shared asset that many entities point at is NOT footage for
 * any of them; it is a stand-in, and a stand-in is reported as such instead of
 * being silently presented as that county's own film.
 *
 * This is what stops 45 counties from all showing one seedance-hero.mp4.
 */
class MediaMapping
{
    public const DISTINCT = 'distinct';        // key carries this entity's slug  → render it
    public const REPRESENTATIVE = 'representative'; // shared stand-in            → do not render as own
    public const MISSING = 'missing';          // row exists, object absent      → do not render

    /** Every key currently in the bucket (cached for the request). */
    public static function r2Keys(): array
    {
        static $keys = null;
        if ($keys !== null) {
            return $keys;
        }
        try {
            $keys = Storage::disk('r2')->allFiles();
        } catch (Throwable) {
            $keys = [];
        }

        return $keys;
    }

    /** Hash-set view of the bucket listing, so membership is O(1) not O(n). */
    public static function r2KeySet(): array
    {
        static $set = null;
        if ($set !== null) {
            return $set;
        }
        $set = [];
        foreach (self::r2Keys() as $k) {
            $set[$k] = true;
        }

        return $set;
    }

    public static function inR2(?string $path): bool
    {
        return $path !== null && $path !== '' && isset(self::r2KeySet()[$path]);
    }

    /** Tokens that prove a key belongs to this entity. */
    private static function tokens(string $slug, int $id, ?string $code = null): array
    {
        $t = [strtolower($slug)];
        if ($code) {
            $t[] = strtolower(str_replace('-', '', $code));
        }
        // Institutions have no code; county ids are not unique across tables so
        // they are deliberately NOT used as a token.
        return array_values(array_filter(array_unique($t)));
    }

    public static function classify(?MediaAsset $asset, string $slug, int $id, ?string $code = null): array
    {
        if (! $asset) {
            return ['state' => self::MISSING, 'reason' => 'no admin row for this slot'];
        }
        if (! self::inR2($asset->path)) {
            return ['state' => self::MISSING, 'reason' => 'object not present in R2: ' . $asset->path];
        }

        $path = strtolower((string) $asset->path);
        foreach (self::tokens($slug, $id, $code) as $tok) {
            if ($tok !== '' && str_contains($path, $tok)) {
                return ['state' => self::DISTINCT, 'reason' => "key carries \"{$tok}\""];
            }
        }

        return ['state' => self::REPRESENTATIVE, 'reason' => 'shared stand-in, not footage of this entity'];
    }

    /**
     * The hero a county may actually render. Returns video=null whenever the
     * only thing available is a stand-in — the caller then shows its own
     * fallback tile rather than borrowing another place's film.
     */
    public static function countyHero(County $county, ?MediaAssetIndex $index = null): array
    {
        $asset = $index
            ? $index->forSlot(County::class, (int) $county->id, 'hero_video')
            : MediaAsset::query()
                ->where('owner_type', County::class)
                ->where('owner_id', $county->id)
                ->where('slot', 'hero_video')
                ->where('status', 'ready')
                ->with('derivatives')
                ->latest('id')
                ->first();

        $verdict = self::classify($asset, (string) $county->slug, (int) $county->id, $county->code ?? null);

        if ($verdict['state'] !== self::DISTINCT) {
            return ['video' => null, 'poster' => null, 'state' => $verdict['state'], 'reason' => $verdict['reason'], 'path' => $asset?->path];
        }

        return [
            'video' => $asset->mp4Url(),
            'poster' => $asset->posterUrl(),
            'hover' => $asset->hoverLoopUrl(),
            'state' => self::DISTINCT,
            'reason' => $verdict['reason'],
            'path' => $asset->path,
        ];
    }

    /**
     * The county's own fallback still image (slot `fallback_image`), used on the
     * tile whenever the county has no DISTINCT film of its own. Returns null when
     * no image is bound, so the caller falls back to its branded gradient tile.
     */
    public static function countyFallbackImage(County $county, ?MediaAssetIndex $index = null): ?string
    {
        $asset = $index
            ? $index->forSlot(County::class, (int) $county->id, ['fallback_image', 'hero_image'])
            : MediaAsset::query()
                ->where('owner_type', County::class)
                ->where('owner_id', $county->id)
                ->whereIn('slot', ['fallback_image', 'hero_image'])
                ->where('status', 'ready')
                ->latest('id')
                ->first();

        if (! $asset || ! self::inR2($asset->path)) {
            return null;
        }

        return $asset->thumbnailUrl() ?? $asset->url();
    }

    /**
     * Bulk-resolve every county's hero film and fallback still.
     *
     * Semantics are identical to calling countyHero() + countyFallbackImage()
     * per county — the newest ready row per (owner, slot) wins, a shared
     * stand-in is reported as REPRESENTATIVE rather than rendered as the
     * county's own film, and every published path must still exist in R2 — but
     * the per-county lookups collapse into four bulk queries. On the homepage
     * this replaces 94 media_assets lookups and 47 derivative loads.
     *
     * @param  iterable  $counties  County models (id, slug and code required)
     * @return array{hero: array<string,array>, fallback: array<string,?string>}
     */
    public static function countyMediaMaps(iterable $counties): array
    {
        $counties = collect($counties);
        $ids = $counties->pluck('id')->map(fn ($i) => (int) $i)->filter()->unique()->values()->all();

        if (empty($ids)) {
            return ['hero' => [], 'fallback' => []];
        }

        // One query per slot group, derivatives eager-loaded so the URL helpers
        // never fall back to a lazy load per asset.
        $heroRows = MediaAsset::query()
            ->where('owner_type', County::class)
            ->whereIn('owner_id', $ids)
            ->where('slot', 'hero_video')
            ->where('status', 'ready')
            ->with('derivatives')
            ->orderBy('id')
            ->get()
            ->groupBy('owner_id')
            ->map(fn ($rows) => $rows->last());   // highest id wins, as latest('id') did

        $stillRows = MediaAsset::query()
            ->where('owner_type', County::class)
            ->whereIn('owner_id', $ids)
            ->whereIn('slot', ['fallback_image', 'hero_image'])
            ->where('status', 'ready')
            ->with('derivatives')
            ->orderBy('id')
            ->get()
            ->groupBy('owner_id')
            ->map(fn ($rows) => $rows->last());

        $hero = [];
        $fallback = [];

        foreach ($counties as $county) {
            $asset = $heroRows->get($county->id);
            $verdict = self::classify($asset, (string) $county->slug, (int) $county->id, $county->code ?? null);

            $hero[$county->slug] = $verdict['state'] === self::DISTINCT
                ? [
                    'video' => $asset->mp4Url(),
                    'poster' => $asset->posterUrl(),
                    'hover' => $asset->hoverLoopUrl(),
                    'state' => self::DISTINCT,
                    'reason' => $verdict['reason'],
                    'path' => $asset->path,
                ]
                : [
                    'video' => null,
                    'poster' => null,
                    'state' => $verdict['state'],
                    'reason' => $verdict['reason'],
                    'path' => $asset?->path,
                ];

            $still = $stillRows->get($county->id);
            $fallback[$county->slug] = ($still && self::inR2($still->path))
                ? ($still->thumbnailUrl() ?? $still->url())
                : null;
        }

        return ['hero' => $hero, 'fallback' => $fallback];
    }

    /** The landing-page stand-in film — never footage of any individual county. */
    public static function standInPaths(): array
    {
        return ['landing/hero/seedance-hero.mp4', 'landing/hero/seedance-hero.webm'];
    }

    /** Same rule for institutions. */
    public static function institutionHero(CountyInstitution $inst, ?MediaAssetIndex $index = null): array
    {
        foreach ([CountyInstitution::class, 'institution'] as $type) {
            $asset = $index
                ? $index->forSlot($type, (int) $inst->id, ['hero_video', 'institution_video'])
                : MediaAsset::query()
                    ->where('owner_type', $type)
                    ->where('owner_id', $inst->id)
                    ->whereIn('slot', ['hero_video', 'institution_video'])
                    ->where('status', 'ready')
                    ->with('derivatives')
                    ->latest('id')
                    ->first();

            if (! $asset) {
                continue;
            }
            $verdict = self::classify($asset, (string) $inst->slug, (int) $inst->id);
            if ($verdict['state'] === self::DISTINCT) {
                return ['video' => $asset->mp4Url(), 'poster' => $asset->posterUrl(), 'state' => self::DISTINCT, 'reason' => $verdict['reason'], 'path' => $asset->path];
            }

            return ['video' => null, 'poster' => null, 'state' => $verdict['state'], 'reason' => $verdict['reason'], 'path' => $asset->path];
        }

        return ['video' => null, 'poster' => null, 'state' => self::MISSING, 'reason' => 'no admin row for this slot', 'path' => null];
    }
}
