<?php

namespace App\Console\Commands;

use App\Jobs\ImageVariantJob;
use App\Models\County;
use App\Models\CountyInstitution;
use App\Models\Marketplace\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SweepImageVariants extends Command
{
    protected $signature = 'media:sweep-image-variants {--limit=6 : Max jobs per run}';

    protected $description = 'Dispatch ImageVariantJobs for images that have no variants yet (counties, institutions, products)';

    private function absoluteUrl(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        return media_url() . '/' . ltrim($path, '/');
    }

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $dispatched = 0;

        $have = DB::table('image_variants')->pluck('source_hash')->flip();

        // 1. County profile images
        $counties = County::whereNotNull('profile_image')->get();
        foreach ($counties as $c) {
            if ($dispatched >= $limit) break;
            $url = $this->absoluteUrl($c->profile_image);
            if (!$url || $have->has(md5($url))) continue;
            ImageVariantJob::dispatch(County::class, $c->id, $url);
            $this->line("  county {$c->slug}: {$url}");
            $dispatched++;
        }

        // 2. County institution logo/cover images
        $insts = CountyInstitution::whereNotNull('logo_url')->orWhereNotNull('cover_image_url')->get();
        foreach ($insts as $i) {
            if ($dispatched >= $limit) break;
            $url = $this->absoluteUrl($i->logo_url ?? $i->cover_image_url);
            if (!$url || $have->has(md5($url))) continue;
            ImageVariantJob::dispatch(CountyInstitution::class, $i->id, $url);
            $this->line("  institution {$i->slug}: {$url}");
            $dispatched++;
        }

        // 3. Marketplace product primary images
        $products = Product::with('images')->whereHas('images')->get();
        foreach ($products as $p) {
            if ($dispatched >= $limit) break;
            $img = $p->images->first();
            if (!$img || !$img->url || $have->has(md5($img->url))) continue;
            ImageVariantJob::dispatch(Product::class, $p->id, $this->absoluteUrl($img->url));
            $this->line("  product {$p->slug}: {$img->url}");
            $dispatched++;
        }

        $this->info("Dispatched {$dispatched} image-variant jobs.");
        return 0;
    }
}