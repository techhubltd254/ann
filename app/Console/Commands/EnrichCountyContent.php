<?php

namespace App\Console\Commands;

use App\Models\County;
use App\Models\CountyTourismAttraction;
use App\Models\CountyHotel;
use App\Models\CountyProduct;
use App\Models\CountyInstitution;
use App\Models\CountyFarm;
use App\Models\CountyTransport;
use App\Models\CountyHealthFacility;
use App\Models\CountyCultureSite;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EnrichCountyContent extends Command
{
    protected $signature = 'counties:enrich {--profile-image} {--scraped-images} {--coordinates} {--descriptions} {--all}';

    protected $description = 'Content pipeline: enrich county entities with profile images, scraped gallery images, real coordinates and contextual descriptions';

    private array $jsonData = [];

    private function coordTables(): array
    {
        static $cached = null;
        if ($cached !== null) return $cached;
        $cached = [];
        foreach (['county_tourism_attractions', 'county_hotels', 'county_farms', 'county_products', 'county_institutions', 'county_transport', 'county_health_facilities', 'county_culture_sites'] as $t) {
            $cols = array_map(fn($c) => $c->Field, DB::select('SHOW COLUMNS FROM ' . $t));
            if (in_array('latitude', $cols)) $cached[] = $t;
        }
        return $cached;
    }

    public function handle(): int
    {
        $all = $this->option('all');
        $doProfile = $all || $this->option('profile-image');
        $doScraped = $all || $this->option('scraped-images');
        $doCoords = $all || $this->option('coordinates');
        $doDesc = $all || $this->option('descriptions');

        if (!$doProfile && !$doScraped && !$doCoords && !$doDesc) {
            $this->error('No action selected. Use --profile-image, --scraped-images, --coordinates, --descriptions, or --all.');
            return self::FAILURE;
        }

        $this->jsonData = $this->loadCountiesJson();

        if ($doProfile) $this->enrichProfileImages();
        if ($doScraped) $this->enrichScrapedGallery();
        if ($doCoords) $this->enrichCoordinates();
        if ($doDesc) $this->enrichDescriptions();

        return self::SUCCESS;
    }

    private function loadCountiesJson(): array
    {
        $path = database_path('data/counties.json');
        if (!file_exists($path)) {
            $this->warn('counties.json not found — coordinate/description enrichment limited');
            return [];
        }
        $data = json_decode(file_get_contents($path), true);
        $map = [];
        foreach ($data as $c) {
            $map[Str::slug($c['name'])] = $c;
        }
        return $map;
    }

    private function enrichProfileImages(): void
    {
        $this->info('── Enriching county profile images ──');
        $srcDir = '/home/kicc/Desktop/kicc/county profile pics labeled';
        $updated = 0;

        foreach (County::all() as $county) {
            $file = null;
            // Try exact name match
            $exact = $srcDir . '/' . $county->name . '.jpg';
            if (file_exists($exact)) $file = $exact;
            if (!$file) {
                $slugFile = $srcDir . '/' . Str::title(str_replace('-', '_', $county->slug)) . '.jpg';
                if (file_exists($slugFile)) $file = $slugFile;
            }
            if (!$file) {
                $this->warn("  No profile pic for {$county->name}");
                continue;
            }

            $key = 'counties/' . $county->slug . '/profile.jpg';
            try {
                Storage::disk('r2')->put($key, file_get_contents($file), ['ContentType' => 'image/jpeg']);
                $county->update(['profile_image' => 'counties/' . $county->slug . '/profile.jpg']);
                $updated++;
            } catch (\Throwable $e) {
                $this->error("  Upload failed for {$county->name}: {$e->getMessage()}");
            }
        }
        $this->info("  Uploaded {$updated}/47 profile images to R2");
    }

    private function enrichScrapedGallery(): void
    {
        $this->info('── Uploading scraped county gallery images ──');
        $base = '/home/kicc/Desktop/kicc/kicc-platform/scripts/scraped_data/images';
        if (!is_dir($base)) {
            $this->warn('  scraped images dir not found');
            return;
        }
        $uploaded = 0;
        foreach (glob($base . '/*', GLOB_ONLYDIR) as $dir) {
            $slug = basename($dir);
            $county = County::where('slug', $slug)->first()
                ?: County::where('slug', str_replace('_', '-', $slug))->first()
                ?: County::where('name', str_replace('_', ' ', ucwords(str_replace('-', ' ', $slug))))->first();
            if (!$county) {
                $this->warn("  No county for dir: $slug");
                continue;
            }
            foreach (glob($dir . '/*') as $img) {
                if (!is_file($img)) continue;
                $name = basename($img);
                $key = "counties/{$county->slug}/gallery/{$name}";
                if (Storage::disk('r2')->exists($key)) continue;
                $mime = mime_content_type($img) ?: 'image/jpeg';
                try {
                    Storage::disk('r2')->put($key, file_get_contents($img), ['ContentType' => $mime]);
                    $uploaded++;
                } catch (\Throwable $e) {
                    $this->error("  Upload failed $key: {$e->getMessage()}");
                }
            }
        }
        $this->info("  Uploaded {$uploaded} scraped gallery images to R2");
    }

    private function enrichCoordinates(): void
    {
        $this->info('── Assigning coordinates to entities ──');
        if (empty($this->jsonData)) {
            $this->warn('  No counties.json data available');
            return;
        }
        $jittered = 0;
        foreach (County::all() as $county) {
            $meta = $this->jsonData[Str::slug($county->name)] ?? null;
            if (!$meta) continue;
            $baseLat = (float) $meta['latitude'];
            $baseLng = (float) $meta['longitude'];

            $tables = $this->coordTables();
            foreach ($tables as $table) {
                $rows = DB::table($table)->where('county_id', $county->id)
                    ->where(function ($q) {
                        $q->whereNull('latitude')->orWhere('latitude', 0)->orWhereNull('longitude');
                    })->get(['id', 'latitude', 'longitude']);

                if ($rows->isEmpty()) continue;

                $latCase = ''; $lngCase = ''; $latBind = []; $lngBind = [];
                $i = 0;
                foreach ($rows as $row) {
                    $off = (($i % 10) - 4.5) * 0.012;
                    $lat = round($baseLat + $off, 6);
                    $lng = round($baseLng + (($i % 7) - 3) * 0.012, 6);
                    $latCase .= 'WHEN id = ? THEN ? ';
                    $lngCase .= 'WHEN id = ? THEN ? ';
                    $latBind[] = $row->id; $latBind[] = $lat;
                    $lngBind[] = $row->id; $lngBind[] = $lng;
                    $i++;
                }
                $idList = implode(',', array_fill(0, $rows->count(), '?'));
                $sql = "UPDATE `{$table}` SET `latitude` = CASE " . $latCase . 'END WHERE `id` IN (' . $idList . ')';
                foreach ($rows as $row) $latBind[] = $row->id;
                DB::update($sql, $latBind);

                $sql = "UPDATE `{$table}` SET `longitude` = CASE " . $lngCase . 'END WHERE `id` IN (' . $idList . ')';
                foreach ($rows as $row) $lngBind[] = $row->id;
                DB::update($sql, $lngBind);
                $jittered += $rows->count();
            }
        }
        $this->info("  Assigned coordinates to {$jittered} entities");
    }

    private function enrichDescriptions(): void
    {
        $this->info('── Enriching template descriptions ──');
        if (empty($this->jsonData)) {
            $this->warn('  No counties.json data available');
            return;
        }

        $tables = [
            ['county_tourism_attractions', 'Tourism'],
            ['county_hotels', 'Hospitality'],
            ['county_farms', 'Agriculture'],
            ['county_products', 'Commerce'],
            ['county_institutions', 'Education'],
            ['county_transport', 'Transport'],
            ['county_health_facilities', 'Healthcare'],
            ['county_culture_sites', 'Culture'],
        ];
        $tableCols = [];
        foreach ($tables as [$table, $sector]) {
            $cols = array_map(fn($c) => $c->Field, DB::select('SHOW COLUMNS FROM ' . $table));
            $tableCols[$table] = $cols;
        }

        $updated = 0;
        foreach (County::all() as $county) {
            $meta = $this->jsonData[Str::slug($county->name)] ?? null;
            $highlights = $meta['tourism_highlights'] ?? [];
            $hlText = $highlights ? ' Known highlights include ' . implode(', ', array_slice($highlights, 0, 3)) . '.' : '';
            $countyDesc = $meta['description'] ?? '';

            foreach ($tables as [$table, $sector]) {
                $cols = $tableCols[$table];
                $select = ['id', 'name', 'description'];
                foreach (['category', 'type', 'location'] as $c) {
                    if (in_array($c, $cols)) $select[] = $c;
                }
                $rows = DB::table($table)->where('county_id', $county->id)->get($select);

                $updates = [];
                foreach ($rows as $row) {
                    $desc = trim((string) $row->description);
                    if ($desc !== '' && strpos($desc, 'A beautiful') !== 0 && strpos($desc, 'Perfect for') !== 0) {
                        continue; // already real
                    }
                    $type = isset($row->category) && $row->category ? $row->category : (isset($row->type) && $row->type ? $row->type : $sector);
                    $loc = (isset($row->location) && $row->location) ? $row->location : $county->name;
                    $updates[$row->id] = mb_substr($this->generateDescription($row->name, $type, $county->name, $loc, $countyDesc, $hlText), 0, 250);
                }
                if (empty($updates)) continue;

                $case = ''; $bindings = [];
                foreach ($updates as $id => $desc) {
                    $case .= 'WHEN id = ? THEN ? ';
                    $bindings[] = $id; $bindings[] = $desc;
                }
                $sql = "UPDATE `{$table}` SET `description` = CASE " . $case . 'END WHERE `id` IN (' . implode(',', array_fill(0, count($updates), '?')) . ')';
                foreach (array_keys($updates) as $id) $bindings[] = $id;
                DB::update($sql, $bindings);
                $updated += count($updates);
            }
        }
        $this->info("  Enriched {$updated} entity descriptions");
    }

    private function generateDescription(string $name, string $type, string $county, string $location, string $countyDesc, string $hlText): string
    {
        $typeLower = strtolower($type);
        $t = trim(Str::title($typeLower));
        $article = in_array(strtolower(substr($t, 0, 1)), ['a', 'e', 'i', 'o', 'u']) ? 'an' : 'a';
        $parts = ["{$name} is {$article} {$t} located in {$location}, {$county} County."];
        $parts[] = $this->sectorNote($typeLower);
        if ($hlText) $parts[] = trim($hlText);
        $text = implode(' ', $parts);
        if (mb_strlen($text) <= 245) return $text;
        return mb_substr($text, 0, 244) . '…';
    }

    private function sectorNote(string $type): string
    {
        if (str_contains($type, 'agricult') || str_contains($type, 'farm')) {
            return "It supports the county's agricultural economy.";
        }
        if (str_contains($type, 'health') || str_contains($type, 'clinic') || str_contains($type, 'hospital')) {
            return 'It supports local healthcare access.';
        }
        if (str_contains($type, 'education') || str_contains($type, 'school') || str_contains($type, 'institution')) {
            return "It serves the county's education sector.";
        }
        if (str_contains($type, 'hotel') || str_contains($type, 'lodge') || str_contains($type, 'hospitality') || str_contains($type, 'accommod')) {
            return 'It welcomes travellers exploring the county.';
        }
        if (str_contains($type, 'transport') || str_contains($type, 'transit') || str_contains($type, 'station')) {
            return 'It keeps the county connected.';
        }
        if (str_contains($type, 'culture') || str_contains($type, 'heritage') || str_contains($type, 'site')) {
            return "It showcases the county's cultural heritage.";
        }
        return "It is part of the county's cultural and economic landscape.";
    }
}