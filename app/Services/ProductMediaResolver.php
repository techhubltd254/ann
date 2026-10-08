<?php

namespace App\Services;

use App\Models\County;
use App\Models\CountyInstitution;
use App\Models\Marketplace\Product;
use App\Models\MediaAsset;
use App\Support\MediaMapping;

/** Display provenance explicitly; never substitute a peer's footage for a product. */
class ProductMediaResolver
{
    private function images(string $type, int $id): ?array
    {
        $assets = MediaAsset::where('owner_type', $type)->where('owner_id', $id)
            ->where('status', 'ready')->where('kind', 'image')->latest('id')->get();
        foreach ($assets as $asset) {
            if ($asset->disk !== 'r2' || !MediaMapping::inR2($asset->path)) continue;
            return ['url' => url('/media/video/' . $asset->path),
                'illustrative' => (bool) ($asset->metadata['illustrative'] ?? false) || str_contains($asset->path, '/image/fallback-')];
        }
        return null;
    }

    private function poster(string $type, int $id, string $slug): ?string
    {
        $assets = MediaAsset::where('owner_type', $type)->where('owner_id', $id)
            ->where('status', 'ready')->where('kind', 'video')->with('derivatives')->latest('id')->get();
        foreach ($assets as $asset) {
            if (MediaMapping::classify($asset, $slug, $id)['state'] !== MediaMapping::DISTINCT) continue;
            foreach ($asset->derivatives as $d) {
                if (in_array($d->kind, ['poster', 'thumb', 'webp'], true) && MediaMapping::inR2($d->path)) {
                    return url('/media/video/' . $d->path);
                }
            }
        }
        return null;
    }

    private function stored(?string $url): ?string
    {
        $url = Product::usableImageUrl($url);
        if (!$url) return null;
        // Validate known local/R2 references; do not follow arbitrary URLs server-side.
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        if (str_starts_with($path, '/media/video/')) {
            $key = rawurldecode(substr($path, strlen('/media/video/')));
            return MediaMapping::inR2($key) ? url('/media/video/' . $key) : null;
        }
        return null; // An external image needs explicit import/verification first.
    }

    public function resolve(Product $product): array
    {
        $own = $this->images(Product::class, (int) $product->id);
        if ($own) return $own + ['source' => 'product', 'label' => $own['illustrative'] ? 'Illustrative product image' : null];
        foreach ($product->images as $image) {
            if ($url = $this->stored($image->url)) return ['url' => $url, 'source' => 'product', 'illustrative' => false, 'label' => null];
        }
        foreach ($product->variants as $variant) {
            if ($url = $this->stored($variant->image_url)) return ['url' => $url, 'source' => 'product', 'illustrative' => false, 'label' => null];
        }
        // Use the explicit FK only, and require the institution to belong to the same county.
        $inst = $product->institution_id ? CountyInstitution::find($product->institution_id) : null;
        if ($inst && (int) $inst->county_id === (int) $product->county_id) {
            $image = $this->images(CountyInstitution::class, (int) $inst->id);
            $url = $image['url'] ?? $this->poster(CountyInstitution::class, (int) $inst->id, (string) $inst->slug);
            if ($url) return ['url' => $url, 'source' => 'institution', 'illustrative' => true,
                'label' => $inst->name . ' imagery · not a product photo'];
        }
        $county = $product->county;
        if ($county) {
            $image = $this->images(County::class, (int) $county->id);
            $url = $image['url'] ?? $this->poster(County::class, (int) $county->id, (string) $county->slug);
            if ($url) return ['url' => $url, 'source' => 'county', 'illustrative' => true,
                'label' => $county->name . ' illustrative imagery · not a product photo'];
        }
        return ['url' => null, 'source' => 'missing', 'illustrative' => false, 'label' => 'No verified product image uploaded'];
    }
}
