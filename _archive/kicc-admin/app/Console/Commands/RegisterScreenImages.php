<?php

namespace App\Console\Commands;

use App\Models\ScreenImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RegisterScreenImages extends Command
{
    protected $signature = 'screen:register-images
        {--scan : Scan all county and sector photo directories}
        {--list : List registered images}
        {--stats : Show image statistics}
        {--county= : Filter by county slug}';

    protected $description = 'Register images for screen video generation';

    protected array $sectorKeywords = [
        'hospital' => ['health'], 'cruise' => ['tourism', 'transport'],
        'fort' => ['tourism', 'culture'], 'park' => ['tourism', 'environment'],
        'market' => ['trade', 'agriculture'], 'ferry' => ['transport'],
        'marine' => ['tourism', 'environment', 'fisheries'],
        'airport' => ['transport'], 'tusks' => ['tourism', 'culture'],
        'beach' => ['tourism'], 'resort' => ['tourism'],
        'old town' => ['tourism', 'culture'], 'port' => ['transport', 'trade'],
        'sgr' => ['transport'], 'rail' => ['transport'],
        'spice' => ['trade', 'agriculture'], 'forest' => ['environment', 'tourism'],
        'cashew' => ['agriculture'], 'coconut' => ['agriculture'],
        'ict' => ['technology', 'education'], 'classroom' => ['education'],
        'epz' => ['manufacturing', 'trade'], 'factory' => ['manufacturing'],
        'fish pond' => ['fisheries', 'agriculture'], 'fish' => ['fisheries', 'agriculture'],
        'ruins' => ['tourism', 'culture'], 'creek' => ['tourism', 'environment'],
        'fruit' => ['agriculture', 'manufacturing'], 'processing' => ['manufacturing', 'agriculture'],
        'solar' => ['energy', 'water'], 'borehole' => ['water', 'energy'],
        'swahili' => ['tourism', 'culture'], 'festival' => ['tourism', 'culture'],
        'dhow' => ['tourism', 'culture', 'fisheries'], 'vipingo' => ['tourism', 'sports'],
        'golf' => ['tourism', 'sports'], 'watamu' => ['tourism', 'environment'],
        'youth' => ['education', 'sports'], 'sports' => ['education', 'sports'],
    ];

    protected array $countyFallbackSectors = [
        'mombasa' => ['tourism', 'transport', 'trade', 'manufacturing'],
        'kilifi' => ['tourism', 'agriculture', 'fisheries', 'energy'],
    ];

    public function handle(): int
    {
        $cmd = match (true) {
            $this->option('scan') => 'scan',
            $this->option('list') => 'list',
            $this->option('stats') => 'stats',
            default => null,
        };

        return match ($cmd) {
            'scan' => $this->scanAll(),
            'list' => $this->listImages(),
            'stats' => $this->stats(),
            default => $this->showHelp(),
        };
    }

    protected function scanAll(): int
    {
        $this->info('Scanning county photos...');

        $countyDir = Storage::disk('public')->path('counties');
        $count = 0;
        $existing = ScreenImage::where('source', 'county_scan')->pluck('filename')->toArray();

        foreach (glob($countyDir . '/*/*.*g') as $path) {
            $fname = basename($path);
            if (in_array($fname, $existing)) continue;

            $parts = explode('/', str_replace($countyDir . '/', '', $path));
            $countySlug = $parts[0] ?? null;
            if (!$countySlug || $countySlug === 'shared') continue;

            $analysis = $this->analyzeImage($path);
            ScreenImage::create([
                'filename' => $fname,
                'storage_path' => $path,
                'original_path' => $path,
                'county_id' => $countySlug,
                'sector_ids' => '',
                'tags' => "county:{$countySlug} landmark",
                'quality_score' => $analysis['quality_score'] ?? 0.7,
                'scene_type' => $analysis['scene_type'] ?? 'unknown',
                'width' => $analysis['width'] ?? 0,
                'height' => $analysis['height'] ?? 0,
                'brightness' => $analysis['brightness'] ?? 0.5,
                'contrast' => $analysis['contrast'] ?? 0.3,
                'has_water' => $analysis['has_water'] ?? false,
                'source' => 'county_scan',
            ]);
            $count++;
        }

        $this->info("Registered {$count} county photos (skipped " . count($existing) . ")");

        // Sector photos
        $sectorDirs = [
            'mombasa' => Storage::disk('public')->path('counties/mombasa'),
            'kilifi' => Storage::disk('public')->path('counties/kilifi'),
        ];

        $sCount = 0;
        $sExisting = ScreenImage::where('source', 'sector_scan')->pluck('filename')->toArray();

        foreach ($sectorDirs as $countyId => $dir) {
            foreach (glob($dir . '/*.*g') as $path) {
                $fname = basename($path);
                if (in_array($fname, $sExisting)) continue;

                // Skip hero.jpeg, showcase.mp4, and known county landmark files
                if (in_array($fname, ['hero.jpeg', 'showcase.mp4'])) continue;

                $sectors = $this->inferSectors($fname, $countyId);
                $analysis = $this->analyzeImage($path);
                ScreenImage::create([
                    'filename' => $fname,
                    'storage_path' => $path,
                    'original_path' => $path,
                    'county_id' => $countyId,
                    'sector_ids' => implode(',', $sectors),
                    'tags' => "county:{$countyId} sector:" . implode(',', $sectors),
                    'quality_score' => $analysis['quality_score'] ?? 0.7,
                    'scene_type' => $analysis['scene_type'] ?? 'unknown',
                    'width' => $analysis['width'] ?? 0,
                    'height' => $analysis['height'] ?? 0,
                    'brightness' => $analysis['brightness'] ?? 0.5,
                    'contrast' => $analysis['contrast'] ?? 0.3,
                    'has_water' => $analysis['has_water'] ?? false,
                    'source' => 'sector_scan',
                ]);
                $sCount++;
            }
        }

        $this->info("Registered {$sCount} sector photos (skipped " . count($sExisting) . ")");
        $this->info("Total: " . ScreenImage::count() . " images in registry.");

        return Command::SUCCESS;
    }

    protected function listImages(): int
    {
        $countyId = $this->option('county');
        $query = ScreenImage::query();
        if ($countyId) {
            $query->where('county_id', $countyId);
        }

        $images = $query->orderBy('county_id')->orderBy('filename')->get();

        if ($images->isEmpty()) {
            $this->warn('No images found.');
            return Command::SUCCESS;
        }

        $this->info("{$images->count()} images:");
        foreach ($images as $img) {
            $this->line(sprintf(
                "  [%3d] %-35s county=%-10s sectors=%-25s %-10s q=%.2f",
                $img->id,
                Str::limit($img->filename, 33),
                $img->county_id ?? '',
                Str::limit($img->sector_ids ?? '', 23),
                $img->scene_type ?? '',
                $img->quality_score,
            ));
        }

        return Command::SUCCESS;
    }

    protected function stats(): int
    {
        $total = ScreenImage::count();
        $byCounty = ScreenImage::whereNotNull('county_id')
            ->selectRaw('county_id, COUNT(*) as cnt')
            ->groupBy('county_id')
            ->pluck('cnt', 'county_id');
        $byScene = ScreenImage::selectRaw('scene_type, COUNT(*) as cnt')
            ->groupBy('scene_type')
            ->pluck('cnt', 'scene_type');

        $this->info("Total images: {$total}");
        $this->info("By county:    " . $byCounty->toJson());
        $this->info("By scene:     " . $byScene->toJson());

        return Command::SUCCESS;
    }

    protected function inferSectors(string $filename, string $countyId): array
    {
        $name = str_replace(['_', '-'], ' ', strtolower($filename));
        $matched = [];

        foreach ($this->sectorKeywords as $keyword => $sectors) {
            if (str_contains($name, $keyword)) {
                $matched = array_merge($matched, $sectors);
            }
        }

        if (empty($matched)) {
            $matched = $this->countyFallbackSectors[$countyId] ?? [];
        }

        return array_unique($matched);
    }

    protected function analyzeImage(string $path): array
    {
        try {
            [$width, $height] = getimagesize($path);
            return [
                'width' => $width,
                'height' => $height,
                'quality_score' => min(1.0, ($width * $height) / (1920 * 1080)),
                'scene_type' => 'unknown',
                'brightness' => 0.5,
                'contrast' => 0.3,
                'has_water' => false,
            ];
        } catch (\Throwable $e) {
            return [
                'width' => 0, 'height' => 0, 'quality_score' => 0.5,
                'scene_type' => 'unknown', 'brightness' => 0.5,
                'contrast' => 0.3, 'has_water' => false,
            ];
        }
    }

    protected function showHelp(): int
    {
        $this->info('Usage:');
        $this->line('  php artisan screen:register-images --scan    Scan all photo dirs and register');
        $this->line('  php artisan screen:register-images --list    List registered images');
        $this->line('  php artisan screen:register-images --stats   Show image statistics');
        $this->line('  php artisan screen:register-images --list --county=mombasa');
        return Command::SUCCESS;
    }
}
