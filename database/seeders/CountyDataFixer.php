<?php
/**
 * County Data Fixer — imports scraped data, organizes images,
 * updates TiDB records, handles 6 failed counties gracefully.
 *
 * Run: php artisan db:seed --class=CountyDataFixer
 * Or: /home/kicc/Desktop/kicc/kicc-platform/.tools/php/php /tmp/opencode/fix_counties.php
 */

namespace Database\Seeders; // when run as artisan seeder

use App\Models\County;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CountyDataFixer extends Seeder
{
    public function run(): void
    {
        $this->command->info('═══ COUNTY DATA FIXER ═══');
        $this->fixFromScrapedData();
        $this->organizeImages();
        $this->fixMissingCounties();
        $this->linkSectors();
        $this->command->info('✅ County data fix complete');
    }

    protected function fixFromScrapedData(): void
    {
        $path = database_path('data/counties_scraped.json');
        if (!file_exists($path)) { $this->command->warn('No scraped data file'); return; }

        $data = json_decode(file_get_contents($path), true);
        $updated = 0;

        foreach ($data as $c) {
            $slug = $this->slug($c['name']);
            $county = County::where('slug', $slug)->first();
            if (!$county) { $this->command->warn("  County not found: {$c['name']}"); continue; }

            $changes = [];
            if (empty($county->description) && !empty($c['description'])) {
                $county->description = mb_substr($c['description'], 0, 1000);
                $changes[] = 'description';
            }
            if (!empty($c['contacts']['emails']) && empty($county->getAttribute('contact_email'))) {
                // Can't set contact_email as it doesn't exist on counties table — skip
            }
            if (!empty($c['title']) && empty($county->tagline)) {
                $county->tagline = mb_substr($c['title'], 0, 200);
                $changes[] = 'tagline';
            }

            if (!empty($changes)) {
                $county->save();
                $updated++;
                $this->command->line("  {$county->name}: " . implode(', ', $changes));
            }
        }
        $this->command->info("  Updated {$updated} counties from scraped data");
    }

    protected function organizeImages(): void
    {
        $srcDir = storage_path('app/public/counties');
        $files = [];
        foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
            $files = array_merge($files, glob("$srcDir/*.$ext"));
        }
        $moved = 0;

        foreach ($files as $f) {
            $basename = pathinfo($f, PATHINFO_FILENAME);
            $ext = pathinfo($f, PATHINFO_EXTENSION);

            // Parse county slug from filename (e.g. "Mombasa_Fort_Jesus" → mombasa, "Kilifi" → kilifi)
            $parts = explode('_', $basename);
            $countyName = $parts[0];
            $slug = $this->slug($countyName);

            $county = County::where('slug', $slug)->first();
            if (!$county) continue;

            $destDir = "$srcDir/$slug";
            if (!is_dir($destDir)) mkdir($destDir, 0755, true);

            // Determine what this image is for
            $remainder = implode('_', array_slice($parts, 1));
            $sectorMap = [
                'Fort_Jesus' => 'tourism', 'Beach' => 'tourism', 'Marine' => 'tourism',
                'Viewpoint' => 'tourism', 'Botanical' => 'tourism', 'Cave' => 'tourism',
                'Park' => 'tourism', 'Cultural' => 'culture', 'Heritage' => 'culture',
                'Market' => 'products', 'Factory' => 'products', 'Cashew' => 'products',
                'Farm' => 'farms', 'Fish' => 'farms', 'Pond' => 'farms',
                'Hospital' => 'health', 'Health' => 'health', 'Clinic' => 'health',
                'School' => 'education', 'College' => 'education', 'University' => 'education',
                'Hotel' => 'hotels', 'Resort' => 'hotels', 'Lodge' => 'hotels',
                'Road' => 'transport', 'Airport' => 'transport', 'Port' => 'transport', 'Terminal' => 'transport',
            ];

            $destName = 'hero';
            foreach ($sectorMap as $key => $sector) {
                if (strpos($basename, $key) !== false) { $destName = $sector; break; }
            }

            $destPath = "$destDir/$destName.jpeg";
            if (!file_exists($destPath)) {
                copy($f, $destPath);
                $moved++;
            }
        }
        $this->command->info("  Organized $moved images into county directories");
    }

    protected function fixMissingCounties(): void
    {
        $missing = ['busia', 'embu', 'garissa', 'kirinyaga', 'nairobi-city', 'taita-taveta'];
        $fixed = 0;

        foreach ($missing as $slug) {
            $county = County::where('slug', $slug)->first();
            if (!$county) continue;

            $destDir = storage_path("app/public/counties/$slug");
            if (!is_dir($destDir)) mkdir($destDir, 0755, true);

            // Generate a basic hero image placeholder if none exists
            $heroPath = "$destDir/hero.jpeg";
            if (!file_exists($heroPath)) {
                // Create a simple colored placeholder using GD
                if (function_exists('imagecreatetruecolor')) {
                    $img = imagecreatetruecolor(800, 600);
                    $color = imagecolorallocate($img, 13, 27, 87); // navy-ish
                    imagefill($img, 0, 0, $color);
                    imagejpeg($img, $heroPath, 70);
                    imagedestroy($img);
                    $fixed++;
                }
            }

            // Set a generic description if empty
            if (empty($county->description)) {
                $county->description = "{$county->name} County — one of Kenya's 47 counties. Data and images coming soon.";
                $county->save();
            }
        }
        $this->command->info("  Fixed $fixed missing counties with placeholders");
    }

    protected function linkSectors(): void
    {
        $path = database_path('data/scraped_sectors.json');
        if (!file_exists($path)) { $this->command->warn('No scraped sectors file'); return; }

        $data = json_decode(file_get_contents($path), true);
        $linked = 0;
        $created = 0;

        foreach ($data as $item) {
            $countySlug = $this->slug($item['county'] ?? '');
            $county = County::where('slug', $countySlug)->first();
            if (!$county) continue;

            $name = trim($item['name'] ?? $item['sector'] ?? '');
            if (empty($name) || strlen($name) < 3) continue;

            $slug = $this->slug(preg_replace('/[^a-zA-Z0-9\s]/', '', $name));
            $sector = \App\Models\Sector::firstOrCreate(
                ['slug' => mb_substr($slug, 0, 80)],
                [
                    'name' => mb_substr($name, 0, 100),
                    'code' => 'S' . strtoupper(mb_substr(md5($slug), 0, 7)),
                    'description' => "$name sector — {$county->name} County",
                    'is_active' => true,
                ]
            );
            if ($sector->wasRecentlyCreated) $created++;

            if (!$county->sectors()->where('sector_id', $sector->id)->exists()) {
                $county->sectors()->attach($sector->id);
                $linked++;
            }
        }
        $this->command->info("  Linked $linked sectors ($created new) to counties");
    }

    protected function slug(string $name): string
    {
        $s = strtolower(trim($name));
        $s = str_replace(["'", "`", "."], "", $s);
        $s = preg_replace('/[^a-z0-9-]/', '-', $s);
        $s = preg_replace('/-+/', '-', $s);
        return trim($s, '-');
    }
}
