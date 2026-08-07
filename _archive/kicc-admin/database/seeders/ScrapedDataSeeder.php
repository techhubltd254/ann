<?php

namespace Database\Seeders;

use App\Models\County;
use App\Models\Sector;
use Illuminate\Database\Seeder;

class ScrapedDataSeeder extends Seeder
{
    public function run(): void
    {
        $countiesPath = database_path('data/counties_scraped.json');
        if (!file_exists($countiesPath)) {
            $this->command->error("File not found: $countiesPath — run county_scraper_v2.py first");
            return;
        }

        $counties = json_decode(file_get_contents($countiesPath), true);
        if (empty($counties)) {
            $this->command->error('No data in counties_scraped.json');
            return;
        }

        $updatedDesc = 0;
        $linkedSectors = 0;

        foreach ($counties as $data) {
            $countySlug = \Illuminate\Support\Str::slug($data['name']);
            $county = County::where('slug', $countySlug)->first();

            if (!$county) {
                $this->command->warn("  County not found: {$data['name']} (slug: $countySlug)");
                continue;
            }

            // Update description if empty
            if (empty($county->description) && !empty($data['description'])) {
                $county->description = mb_substr($data['description'], 0, 1000);
                $county->save();
                $updatedDesc++;
            }

            // Link sectors to county
            foreach ($data['sectors'] ?? [] as $sectorData) {
                $name = mb_substr(trim($sectorData['name'] ?? ''), 0, 100);
                if (empty($name) || strlen($name) < 3) continue;

                $slug = \Illuminate\Support\Str::slug(mb_substr(preg_replace('/[^a-zA-Z0-9\s]/', '', $name), 0, 80));
                $code = 'S' . strtoupper(mb_substr(md5($slug . $name), 0, 7));
                $sector = Sector::firstOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $name,
                        'code' => $code,
                        'description' => "{$name} sector — {$county->name} County",
                        'is_active' => true,
                    ]
                );

                if (!$county->sectors()->where('sector_id', $sector->id)->exists()) {
                    $county->sectors()->attach($sector->id);
                    $linkedSectors++;
                }
            }

            $this->command->line("  {$county->name}: desc=" . (empty($data['description']) ? '✗' : '✓')
                . " sectors=" . count($data['sectors'] ?? []));
        }

        $this->command->info("✅ Updated {$updatedDesc} county descriptions");
        $this->command->info("✅ Linked {$linkedSectors} sectors to counties");
    }
}
