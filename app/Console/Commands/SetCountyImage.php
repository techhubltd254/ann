<?php

namespace App\Console\Commands;

use App\Models\County;
use App\Models\MediaAsset;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Bind a county's own still image to that county, end to end.
 *
 * This is the real backend path for option one: the bytes go to R2 under a key
 * that carries the county's own slug, a media_assets row is registered against
 * (owner_type=County, owner_id, slot), and MediaMapping::countyFallbackImage()
 * then resolves it for the tile. Nothing is front-end-only: the county page
 * reads the row from TiDB and the browser fetches the bytes through the media
 * proxy.
 */
class SetCountyImage extends Command
{
    protected $signature = 'kicc:county-image
        {slug : the county slug}
        {file : path to a local image file}
        {--slot=fallback_image : the media slot to bind}
        {--alt= : alt text for the image}
        {--dry-run : print the R2 key and target row without writing anything}';

    protected $description = "Upload a county's own image to R2 and register it as that county's still (the image shown when the county has no film of its own).";

    public function handle(): int
    {
        $slug = (string) $this->argument('slug');
        $county = County::where('slug', $slug)->first();

        if (! $county) {
            $this->error("No county with slug '{$slug}'.");
            return self::FAILURE;
        }

        $file = (string) $this->argument('file');
        if (! is_file($file)) {
            $this->error("No readable file at {$file}.");
            return self::FAILURE;
        }

        $ext  = strtolower(pathinfo($file, PATHINFO_EXTENSION)) ?: 'webp';
        $mime = @mime_content_type($file) ?: 'image/webp';
        $hash = substr(sha1_file($file), 0, 12);
        $key  = "counties/{$county->slug}/image/fallback-{$hash}.{$ext}";
        $slot = (string) $this->option('slot');

        $this->line("county : {$county->name} (#{$county->id})");
        $this->line("r2 key : {$key}");
        $this->line("slot   : {$slot}");
        $this->line("bytes  : " . filesize($file));

        if ($this->option('dry-run')) {
            $this->info('dry-run — nothing written.');
            return self::SUCCESS;
        }

        $bytes = file_get_contents($file);
        Storage::disk('r2')->put($key, $bytes);

        if (! Storage::disk('r2')->exists($key)) {
            $this->error('R2 write failed; no row registered.');
            return self::FAILURE;
        }

        $dim = @getimagesize($file) ?: [null, null];

        $asset = MediaAsset::updateOrCreate(
            [
                'owner_type' => County::class,
                'owner_id'   => $county->id,
                'slot'       => $slot,
            ],
            [
                'uuid'          => (string) Str::uuid(),
                'disk'          => 'r2',
                'path'          => $key,
                'original_name' => basename($file),
                'mime'          => $mime,
                'kind'          => 'image',
                'size_bytes'    => strlen($bytes),
                'width'         => $dim[0] ?? null,
                'height'        => $dim[1] ?? null,
                'status'        => 'ready',
                'alt_text'      => $this->option('alt') ?: ($county->name . ' county'),
            ]
        );

        $this->info("registered media_assets#{$asset->id}");
        $this->line('served : ' . ($asset->thumbnailUrl() ?? $asset->url()));

        return self::SUCCESS;
    }
}
