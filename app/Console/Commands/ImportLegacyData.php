<?php

namespace App\Console\Commands;

use App\Models\MediaAsset;
use App\Models\Record;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ImportLegacyData extends Command
{
    protected $signature = 'kicc:import-legacy-data';
    protected $description = 'Import data from the legacy kicc database into Record-based schema';

    private array $idMap = []; // old_id => new_uuid

    public function handle(): int
    {
        $this->info('=== KICC Legacy Data Migration ===');

        // Verify legacy DB connection
        try {
            DB::connection('mysql_legacy')->select('SELECT 1');
            $this->info('✓ Legacy database connected.');
        } catch (\Throwable $e) {
            $this->error('✗ Legacy database connection failed: ' . $e->getMessage());
            return 1;
        }

        // Verify new DB has correct schema
        if (!DB::getSchemaBuilder()->hasTable('records')) {
            $this->error('✗ Records table not found. Run migrate first.');
            return 1;
        }

        $this->importUsers();
        $this->importCounties();
        $this->importSectors();
        $this->importCountySectorLinks();
        $this->importInstitutions();
        $this->importMediaAssets();
        $this->importProducts();

        $this->info('');
        $this->info('=== Migration Complete ===');
        $this->info('Counties: ' . Record::where('type', 'counties')->count());
        $this->info('Sectors: ' . Record::where('type', 'sectors')->count());
        $this->info('Institutions: ' . Record::where('type', 'institutions')->count());
        $this->info('Products: ' . Record::where('type', 'products')->count());
        $this->info('Media Assets: ' . MediaAsset::count());
        $this->info('Users: ' . User::count());

        return 0;
    }

    private function importUsers(): void
    {
        $this->info('--- Importing Users ---');
        $legacyUsers = DB::connection('mysql_legacy')
            ->table('users')
            ->whereNull('deleted_at')
            ->get();

        $count = 0;
        foreach ($legacyUsers as $lu) {
            $existing = User::where('email', $lu->email)->first();
            if ($existing) {
                $this->idMap['user_' . $lu->id] = $existing->id;
                continue;
            }
            $user = User::create([
                'name' => $lu->name ?? explode('@', $lu->email)[0],
                'email' => $lu->email,
                'password' => $lu->password ?? Hash::make(Str::random(32)),
                'is_admin' => $lu->id == 1, // User ID 1 is the KICC admin
            ]);
            $this->idMap['user_' . $lu->id] = $user->id;
            $count++;
        }
        $this->info("  ✓ {$count} users imported");
    }

    private function importCounties(): void
    {
        $this->info('--- Importing Counties ---');
        $counties = DB::connection('mysql_legacy')
            ->table('counties')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $count = 0;
        foreach ($counties as $c) {
            $payload = [
                'code' => str_pad($c->id, 3, '0', STR_PAD_LEFT),
                'region' => $c->region ?? null,
                'capital' => $c->capital ?? null,
                'population_2024' => $c->population ?? null,
                'area_km2' => $c->area_sqkm ?? null,
                'primary_sectors' => $c->primary_sectors ?? [],
                'icon_emoji' => $c->icon_emoji ?? null,
                'latitude' => $c->lat ?? null,
                'longitude' => $c->lng ?? null,
                'source_id' => (int) $c->id,
                'source_file' => 'kicc.counties',
                'description' => $c->description ?? null,
            ];

            $record = Record::firstOrCreate(
                ['type' => 'counties', 'slug' => $c->slug],
                [
                    'name' => $c->name,
                    'description' => $c->description ?? null,
                    'status' => 'published',
                    'payload' => $payload,
                ]
            );
            $this->idMap['county_' . $c->id] = $record->id;
            $count++;
        }
        $this->info("  ✓ {$count} counties imported");
    }

    private function importSectors(): void
    {
        $this->info('--- Importing Sectors ---');
        $sectors = DB::connection('mysql_legacy')
            ->table('sectors')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $count = 0;
        foreach ($sectors as $s) {
            $payload = [
                'code' => $s->code ?? null,
                'emoji' => $s->emoji ?? null,
                'icon' => $s->icon ?? null,
                'sort_order' => $s->sort_order ?? 0,
                'source_id' => (int) $s->id,
                'source_file' => 'kicc.sectors',
            ];

            $record = Record::firstOrCreate(
                ['type' => 'sectors', 'slug' => $s->slug],
                [
                    'name' => $s->name,
                    'description' => $s->description ?? null,
                    'status' => 'published',
                    'payload' => $payload,
                ]
            );
            $this->idMap['sector_' . $s->id] = $record->id;
            $count++;
        }
        $this->info("  ✓ {$count} sectors imported");
    }

    private function importCountySectorLinks(): void
    {
        $this->info('--- Importing County-Sector Links ---');
        try {
            $links = DB::connection('mysql_legacy')
                ->table('county_sector')
                ->get();
        } catch (\Throwable) {
            $this->info('  (county_sector table not found, skipping)');
            return;
        }

        foreach ($links as $link) {
            $countyUuid = $this->idMap['county_' . $link->county_id] ?? null;
            $sectorUuid = $this->idMap['sector_' . $link->sector_id] ?? null;

            if ($countyUuid && $sectorUuid) {
                $county = Record::find($countyUuid);
                if ($county) {
                    $payload = $county->payload ?? [];
                    $sectorIds = $payload['sector_ids'] ?? [];
                    if (!in_array($sectorUuid, $sectorIds)) {
                        $sectorIds[] = $sectorUuid;
                    }
                    $payload['sector_ids'] = $sectorIds;
                    $payload['display_on_tile'] = $link->display_on_tile ?? 'yes';
                    $county->update(['payload' => $payload]);
                }
            }
        }
        $this->info("  ✓ County-sector links imported");
    }

    private function importInstitutions(): void
    {
        $this->info('--- Importing Institutions ---');
        $institutions = DB::connection('mysql_legacy')
            ->table('county_institutions')
            ->orderBy('name')
            ->get();

        $count = 0;
        foreach ($institutions as $inst) {
            $countyUuid = $this->idMap['county_' . $inst->county_id] ?? null;

            $payload = [
                'type' => $inst->type ?? null,
                'location' => $inst->location ?? null,
                'phone' => $inst->phone ?? null,
                'email' => $inst->email ?? null,
                'website' => $inst->website ?? null,
                'headquarters' => $inst->headquarters ?? null,
                'founded_year' => $inst->founded_year ?? null,
                'story' => $inst->story ?? null,
                'latitude' => $inst->lat ?? null,
                'longitude' => $inst->lng ?? null,
                'sector_mappings' => $inst->sector_mappings ?? null,
                'products' => $inst->products ?? null,
                'videos' => $inst->videos ?? null,
                'county_id' => $inst->county_id ?? null,
                'source_id' => (int) $inst->id,
                'source_file' => 'kicc.county_institutions',
            ];

            // Parse JSON fields if they're strings
            foreach (['sector_mappings', 'products', 'videos'] as $key) {
                if (is_string($payload[$key])) {
                    $payload[$key] = json_decode($payload[$key], true);
                }
            }

            $record = Record::firstOrCreate(
                ['type' => 'institutions', 'slug' => $inst->slug],
                [
                    'name' => $inst->name,
                    'description' => $inst->description ?? null,
                    'status' => $inst->isPublished ? 'published' : 'draft',
                    'parent_id' => $countyUuid,
                    'payload' => $payload,
                ]
            );
            $this->idMap['inst_' . $inst->id] = $record->id;
            $count++;
        }
        $this->info("  ✓ {$count} institutions imported");
    }

    private function importMediaAssets(): void
    {
        $this->info('--- Importing Media Assets ---');
        try {
            $assets = DB::connection('mysql_legacy')
                ->table('media_assets')
                ->where('kind', 'video')
                ->where('status', 'ready')
                ->orderBy('created_at')
                ->get();
        } catch (\Throwable $e) {
            $this->warn('  (media_assets table error: ' . $e->getMessage() . ')');
            return;
        }

        $count = 0;
        foreach ($assets as $asset) {
            // Resolve owner to a Record
            $recordId = null;

            if ($asset->owner_type === 'App\\Models\\County' || $asset->owner_type === 'county') {
                $recordId = $this->idMap['county_' . $asset->owner_id] ?? null;
            } elseif ($asset->owner_type === 'App\\Models\\CountyInstitution' || $asset->owner_type === 'institution') {
                $recordId = $this->idMap['inst_' . $asset->owner_id] ?? null;
            } elseif ($asset->slot === 'national_hero_video' || $asset->slot === 'national_flag_video') {
                // National-level asset — attach to a national page record
                $nationalPage = Record::where('type', 'pages')->where('slug', 'national')->first();
                if (!$nationalPage) {
                    $nationalPage = Record::create([
                        'type' => 'pages',
                        'slug' => 'national',
                        'name' => 'National Government Hero',
                        'status' => 'published',
                        'payload' => ['source_file' => 'kicc.media_assets'],
                    ]);
                }
                $recordId = $nationalPage->id;
            }

            if (!$recordId) continue;

            try {
                MediaAsset::firstOrCreate(
                    ['path' => $asset->path, 'mime' => $asset->mime ?? 'video/mp4'],
                    [
                        'record_id' => $recordId,
                        'disk' => $asset->disk ?? 'r2',
                        'path' => $asset->path,
                        'original_name' => $asset->original_name ?? basename($asset->path),
                        'mime' => $asset->mime ?? 'video/mp4',
                        'bytes' => $asset->size_bytes ?? 0,
                        'title' => $asset->original_name ?? 'Video',
                        'description' => $asset->slot ?? null,
                        'status' => 'published',
                        'format' => 'standard',
                    ]
                );
                $count++;
            } catch (\Throwable $e) {
                $this->warn("  ⚠ Media asset #{$asset->id} failed: " . $e->getMessage());
            }
        }
        $this->info("  ✓ {$count} media assets imported");
    }

    private function importProducts(): void
    {
        $this->info('--- Importing Products ---');
        $count = 0;

        // Import from institution's products JSON column
        $institutions = DB::connection('mysql_legacy')
            ->table('county_institutions')
            ->whereNotNull('products')
            ->get();

        foreach ($institutions as $inst) {
            $instRecordId = $this->idMap['inst_' . $inst->id] ?? null;
            if (!$instRecordId) continue;

            $products = is_string($inst->products) ? json_decode($inst->products, true) : $inst->products;
            if (!is_array($products)) continue;

            foreach ($products as $prod) {
                $name = $prod['name'] ?? 'Product';
                $slug = Str::slug($name . '-' . substr($instRecordId, 0, 6));
                $payload = [
                    'price' => $prod['price'] ?? null,
                    'unit' => $prod['unit'] ?? null,
                    'category' => $prod['category'] ?? null,
                    'stock' => $prod['stock'] ?? null,
                    'source_file' => 'kicc.county_institutions.products',
                ];

                $existing = Record::where('type', 'products')
                    ->where('name', $name)
                    ->where('parent_id', $instRecordId)
                    ->first();
                if ($existing) continue;

                Record::create([
                    'type' => 'products',
                    'slug' => $slug,
                    'name' => $name,
                    'description' => $prod['description'] ?? null,
                    'status' => 'published',
                    'parent_id' => $instRecordId,
                    'payload' => $payload,
                ]);
                $count++;
            }
        }

        // Import from county_products table
        try {
            $countyProducts = DB::connection('mysql_legacy')
                ->table('county_products')
                ->get();
            foreach ($countyProducts as $cp) {
                $name = $cp->name ?? 'County Product';
                $slug = Str::slug($name . '-' . ($cp->county_id ?? 'cp'));
                $countyUuid = null;
                if ($cp->county_id) {
                    $countyUuid = $this->idMap['county_' . $cp->county_id] ?? null;
                }
                $payload = [
                    'price' => $cp->price ?? null,
                    'unit' => $cp->unit ?? null,
                    'category' => $cp->category ?? null,
                    'county_slug' => $cp->county_slug ?? null,
                    'source_file' => 'kicc.county_products',
                ];

                Record::firstOrCreate(
                    ['type' => 'products', 'slug' => $slug],
                    [
                        'name' => $name,
                        'description' => $cp->description ?? null,
                        'status' => 'published',
                        'parent_id' => $countyUuid,
                        'payload' => $payload,
                    ]
                );
                $count++;
            }
        } catch (\Throwable) {
            // county_products table might not exist
        }

        $this->info("  ✓ {$count} products imported");
    }
}