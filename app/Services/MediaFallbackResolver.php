<?php

namespace App\Services;

use App\Models\County;
use App\Models\CountyInstitution;
use App\Models\CountyTourismAttraction;
use App\Models\CountyHotel;
use App\Models\CountyFarm;
use App\Models\CountyTransport;
use App\Models\CountyHealthFacility;
use App\Models\CountyCultureSite;
use App\Models\CountyProduct;
use App\Models\Exhibition;
use App\Models\Venue;
use App\Models\Ministry;
use App\Models\Agency;
use App\Models\ImageVariant;
use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductImage;
use App\Models\MediaAsset;
use App\Models\SectorEntity;
use App\Services\InstitutionSyncService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MediaFallbackResolver
{
    const CACHE_TTL = 3600;
    const CACHE_PREFIX = 'mf:';

    public function resolve($entity, string $size = 'card'): string
    {
        if ($entity instanceof Product) {
            $media = app(ProductMediaResolver::class)->resolve($entity);
            return $media['url'] ?? $this->defaultUrl($entity);
        }
        $key = $this->cacheKey($entity, $size);
        $url = Cache::remember($key, self::CACHE_TTL, function () use ($entity, $size) {
            $url = $this->resolveTree($entity, $size, 0);
            if ($url && !str_contains($url, 'localhost') && !str_contains($url, 'products.jpeg')) {
                return $url;
            }
            return null;
        });
        if ($url) return $url;
        return $this->defaultUrl($entity);
    }

    public function resolveTree($entity, string $size = 'card', int $depth = 0): ?string
    {
        if ($depth > 6) return null;

        // Tier 1: Own media
        $own = $this->ownMedia($entity);
        if ($own) return $own;

        // Tier 2: Parent institution (for products, sector entities)
        $inst = $this->parentInstitution($entity);
        if ($inst) {
            $instUrl = $this->resolveTree($inst, $size, $depth + 1);
            if ($instUrl) return $instUrl;
        }

        // Tier 3: Sector video frame
        $sectorFrame = $this->sectorVideoFrame($entity);
        if ($sectorFrame) return $sectorFrame;

        // Tier 4: County hero poster
        $countyPoster = $this->countyHeroPoster($entity);
        if ($countyPoster) return $countyPoster;

        // Tier 5: Peer institution in same county+sector
        $peerFrame = $this->peerInstitutionFrame($entity);
        if ($peerFrame) return $peerFrame;

        return null;
    }

    public function ownMedia($entity): ?string
    {
        if ($entity instanceof Product) {
            // Retired-Worker URLs and never-uploaded seed stills are not usable media.
            $img = Product::usableImageUrl($entity->images()->first()?->url);
            if ($img) return $img;
            $variantImg = Product::usableImageUrl($entity->variants->first()?->image_url);
            if ($variantImg) return $variantImg;
        }
        if ($entity instanceof CountyInstitution) {
            if ($entity->logo_url && !str_contains($entity->logo_url, 'svg')) return $entity->logo_url;
            if ($entity->cover_image_url && !str_contains($entity->cover_image_url, 'svg')) return $entity->cover_image_url;
        }
        if ($entity instanceof CountyTourismAttraction) {
            if ($entity->image_url && !str_contains($entity->image_url, 'svg')) return $entity->image_url;
        }
        if ($entity instanceof CountyHotel) {
            if ($entity->image_url && !str_contains($entity->image_url, 'svg')) return $entity->image_url;
        }
        if ($entity instanceof CountyFarm) {
            if ($entity->image_url && !str_contains($entity->image_url, 'svg')) return $entity->image_url;
        }
        if ($entity instanceof CountyTransport) {
            if ($entity->image_url && !str_contains($entity->image_url, 'svg')) return $entity->image_url;
        }
        if ($entity instanceof CountyHealthFacility) {
            if ($entity->image_url && !str_contains($entity->image_url, 'svg')) return $entity->image_url;
        }
        if ($entity instanceof CountyCultureSite) {
            if ($entity->image_url && !str_contains($entity->image_url, 'svg')) return $entity->image_url;
        }
        if ($entity instanceof CountyProduct) {
            if ($entity->image_url && !str_contains($entity->image_url, 'svg')) return $entity->image_url;
        }
        if ($entity instanceof Exhibition) {
            if ($entity->cover_image && !str_contains($entity->cover_image, 'svg')) return $entity->cover_image;
        }
        if ($entity instanceof Venue) {
            if ($entity->cover_image && !str_contains($entity->cover_image, 'svg')) return $entity->cover_image;
        }
        if ($entity instanceof Ministry) {
            if ($entity->logo && !str_contains($entity->logo, 'svg')) return $entity->logo;
        }
        if ($entity instanceof Agency) {
            if ($entity->logo && !str_contains($entity->logo, 'svg')) return $entity->logo;
        }
        if ($entity instanceof SectorEntity) {
            if ($entity->image_url && !str_contains($entity->image_url, 'svg')) return $entity->image_url;
        }
        return null;
    }

    public function parentInstitution($entity): ?CountyInstitution
    {
        if ($entity instanceof Product && $entity->user_id) {
            return CountyInstitution::where('user_id', $entity->user_id)->first();
        }
        if ($entity instanceof SectorEntity) {
            if ($entity->entity_type === CountyInstitution::class || $entity->entity_type === InstitutionSyncService::ENTITY_TYPE) {
                return CountyInstitution::find($entity->entity_id);
            }
        }
        return null;
    }

    public function sectorVideoFrame($entity): ?string
    {
        $sectorSlug = $this->entitySectorSlug($entity);
        if (!$sectorSlug) return null;

        $countyId = $this->entityCountyId($entity);
        if (!$countyId) return null;

        $slugsToTry = [$sectorSlug];
        $asset = MediaAsset::where('owner_type', County::class)
            ->where('owner_id', $countyId)
            ->where('slot', 'sector_video_' . $sectorSlug)
            ->ready()
            ->with('derivatives')
            ->first();

        if (!$asset) {
            $anySector = MediaAsset::where('owner_type', County::class)
                ->where('owner_id', $countyId)
                ->where('slot', 'like', 'sector_video_%')
                ->ready()
                ->with('derivatives')
                ->first();
            $asset = $anySector;
        }

        if (!$asset) return null;

        $poster = $asset->posterUrl();
        if ($poster) return $poster;

        $thumb = $asset->thumbnailUrl();
        if ($thumb) return $thumb;

        $videoUrl = $asset->mp4Url() ?? $asset->url();
        if ($videoUrl) return $this->extractFrame($videoUrl, $asset);

        return null;
    }

    public function extractFrame(string $videoUrl, ?MediaAsset $asset = null): ?string
    {
        // Never fetch caller-controlled URLs. Resolve only registered local object keys.
        if(!$asset){$parts=parse_url($videoUrl);$host=strtolower($parts['host']??'');if(($parts['scheme']??'')!=='https'||!in_array($host,['kicctest.org','media.kicctest.org'],true)||!str_starts_with($parts['path']??'','/media/original/'))return null;$key=rawurldecode(substr($parts['path'],16));if(str_contains($key,'..')||str_contains($key,"\0"))return null;$asset=MediaAsset::where('path',$key)->first();if(!$asset){$d=\App\Models\MediaDerivative::where('path',$key)->first();$asset=$d?MediaAsset::find($d->media_asset_id):null;}}
        if(!$asset||$asset->kind!=='video'||!in_array($asset->disk,['r2','public'],true))return null;

        $hash = md5($videoUrl);
        $existing = ImageVariant::byHash($hash);
        if ($existing) return $existing->cardUrl();

        if (!\Illuminate\Support\Facades\Cache::has("frame:{$hash}")) {
            \App\Jobs\ExtractFrameJob::dispatch($videoUrl, 0)->onQueue('media');
            return null;
        }

        $ffmpeg = trim((string) shell_exec('which ffmpeg 2>/dev/null'));
        if (!$ffmpeg) return null;

        try {
            $tmpVideo = tempnam(sys_get_temp_dir(), 'kicc_mf_') . '.mp4';
            $tmpFrame = tempnam(sys_get_temp_dir(), 'kicc_mf_') . '.jpg';

            $input=\Illuminate\Support\Facades\Storage::disk($asset->disk)->readStream($asset->path);if(!is_resource($input))return null;$output=fopen($tmpVideo,'wb');try{stream_copy_to_stream($input,$output);}finally{fclose($input);fclose($output);}

            $cmd = sprintf(
                '%s -y -ss 1 -i %s -vframes 1 -vf scale=640:-1 -q:v 3 %s 2>/dev/null',
                escapeshellarg($ffmpeg),
                escapeshellarg($tmpVideo),
                escapeshellarg($tmpFrame)
            );
            shell_exec($cmd);

            if (!file_exists($tmpFrame) || filesize($tmpFrame) < 100) {
                $cmd = sprintf(
                    '%s -y -ss 0.5 -i %s -vframes 1 -vf scale=640:-1 -q:v 3 %s 2>/dev/null',
                    escapeshellarg($ffmpeg),
                    escapeshellarg($tmpVideo),
                    escapeshellarg($tmpFrame)
                );
                shell_exec($cmd);
            }

            if (!file_exists($tmpFrame) || filesize($tmpFrame) < 100) {
                @unlink($tmpVideo); @unlink($tmpFrame);
                return null;
            }

            $r2Path = 'fallback/' . $hash . '.jpg';
            Storage::disk('r2')->put($r2Path, file_get_contents($tmpFrame), 'public');
            $frameUrl = media_url() . '/' . $r2Path;

            ImageVariant::create([
                'source_hash' => $hash,
                'source_url' => $videoUrl,
                'card_key' => $r2Path,
                'width' => 640,
                'height' => 480,
            ]);

            @unlink($tmpVideo);
            @unlink($tmpFrame);

            return $frameUrl;
        } catch (\Throwable $e) {
            Log::warning('MediaFallbackResolver frame extraction failed: ' . $e->getMessage());
            return null;
        }
    }

    public function countyHeroPoster($entity): ?string
    {
        $countyId = $this->entityCountyId($entity);
        if (!$countyId) return null;

        $hero = MediaAsset::resolveSlot(County::class, $countyId, 'hero_video');
        if ($hero) {
            // A derivative row is not proof the object exists: the landing stand-in
            // carries a poster path that was never uploaded. Only return a poster
            // whose key is actually in the bucket, otherwise fall through.
            foreach (['poster', 'thumb', 'webp'] as $kind) {
                $d = $hero->derivatives->firstWhere('kind', $kind);
                if ($d && $d->path && \App\Support\MediaMapping::inR2($d->path)) {
                    return media_url() . '/' . ltrim($d->path, '/');
                }
            }
            // Never extract a frame from the shared landing stand-in film.
            if (! str_starts_with((string) $hero->path, 'landing/')) {
                $videoUrl = $hero->mp4Url() ?? $hero->url();
                if ($videoUrl) {
                    $frame = $this->extractFrame($videoUrl, $hero);
                    if ($frame) return $frame;
                }
            }
        }

        // The county's own still (slot fallback_image) is bound for all 47 counties,
        // so a product never falls through to a generic placeholder tile.
        $still = MediaAsset::where('owner_type', County::class)->where('owner_id', $countyId)
            ->whereIn('slot', ['fallback_image', 'hero_image'])->where('status', 'ready')
            ->latest('id')->first();
        if ($still && \App\Support\MediaMapping::inR2($still->path)) {
            $url = $still->thumbnailUrl();
            if (! $url || ! \App\Support\MediaMapping::inR2($still->path)) {
                $url = media_url() . '/' . ltrim($still->path, '/');
            }
            if ($url) return $url;
        }

        $county = County::find($countyId);
        if ($county && $county->profile_image) {
            return media_url() . '/' . ltrim($county->profile_image, '/');
        }

        return null;
    }

    public function peerInstitutionFrame($entity): ?string
    {
        $countyId = $this->entityCountyId($entity);
        if (!$countyId) return null;

        $sectorSlug = $this->entitySectorSlug($entity);
        if (!$sectorSlug) return null;

        $entityId = $entity->id ?? 0;
        $entityClass = get_class($entity);

        $sector = \App\Models\Sector::where('slug', 'like', $sectorSlug . '%')->first();
        if (!$sector) return null;

        $peerEntityIds = SectorEntity::where('county_id', $countyId)
            ->where('sector_id', $sector->id)
            ->whereIn('entity_type', [CountyInstitution::class, InstitutionSyncService::ENTITY_TYPE])
            ->where('entity_id', '!=', $entityId)
            ->pluck('entity_id')
            ->unique()
            ->take(5);

        foreach ($peerEntityIds as $pid) {
            $peer = CountyInstitution::find($pid);
            if (!$peer) continue;
            $hero = MediaAsset::resolveSlot(CountyInstitution::class, $pid, 'hero_video');
            if ($hero) {
                $poster = $hero->posterUrl() ?? $hero->thumbnailUrl();
                if ($poster) return $poster;
                $videoUrl = $hero->mp4Url() ?? $hero->url();
                if ($videoUrl) {
                    $frame = $this->extractFrame($videoUrl, $hero);
                    if ($frame) return $frame;
                }
            }
            if ($peer->logo_url) return $peer->logo_url;
            if ($peer->cover_image_url) return $peer->cover_image_url;
        }

        return null;
    }

    public function entitySectorSlug($entity): ?string
    {
        if ($entity instanceof CountyInstitution) {
            $sector = $entity->sectorEntities()->with('sector')->first()?->sector;
            if ($sector) return explode('-', $sector->slug)[0];
        }
        if ($entity instanceof Product && $entity->county_id) {
            $inst = CountyInstitution::where('county_id', $entity->county_id)
                ->where('user_id', $entity->user_id)
                ->first();
            if ($inst) {
                $sector = $inst->sectorEntities()->with('sector')->first()?->sector;
                if ($sector) return explode('-', $sector->slug)[0];
            }
            $sectorEntity = SectorEntity::where('entity_type', Product::class)
                ->where('entity_id', $entity->id)
                ->with('sector')
                ->first();
            if ($sectorEntity) return explode('-', $sectorEntity->sector->slug)[0];
        }
        if ($entity instanceof SectorEntity) {
            $sector = $entity->sector;
            if ($sector) return explode('-', $sector->slug)[0];
        }
        if ($entity instanceof CountyTourismAttraction) {
            return 'tourism';
        }
        if ($entity instanceof CountyHotel) {
            return 'hospitality';
        }
        return null;
    }

    public function entityCountyId($entity): ?int
    {
        if (method_exists($entity, 'county') && $entity->relationLoaded('county')) {
            return $entity->county?->id;
        }
        if (property_exists($entity, 'county_id') && $entity->county_id) {
            return (int) $entity->county_id;
        }
        if ($entity instanceof Product) {
            return $entity->county_id;
        }
        if ($entity instanceof CountyInstitution) {
            return $entity->county_id;
        }
        if ($entity instanceof SectorEntity) {
            return $entity->county_id;
        }
        return null;
    }

    public function defaultUrl($entity): string
    {
        $name = method_exists($entity, 'name') ? $entity->name : 'KICC';
        $category = null;
        if (property_exists($entity, 'category')) $category = $entity->category;
        if (property_exists($entity, 'type')) $category = $category ?? $entity->type;
        return ThumbnailService::placeholder($name, $category);
    }

    public function cacheKey($entity, string $size = 'card'): string
    {
        $class = get_class($entity);
        return self::CACHE_PREFIX . str_replace('\\', '_', $class) . ':' . $entity->id . ':' . $size;
    }

    public function bustCache($entity): void
    {
        $key = $this->cacheKey($entity);
        Cache::forget($key);
        $keyCard = $this->cacheKey($entity, 'card');
        Cache::forget($keyCard);
        $keyThumb = $this->cacheKey($entity, 'thumb');
        Cache::forget($keyThumb);
        $keyHero = $this->cacheKey($entity, 'hero');
        Cache::forget($keyHero);
    }
}