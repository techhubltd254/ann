<?php

namespace App\Console\Commands;

use Database\Seeders\MurangaAccurateDataSeeder;
use App\Models\County;
use App\Models\MediaAsset;
use App\Services\MediaLibraryService;
use Illuminate\Console\Command;

class SeedMurangaData extends Command
{
    protected $signature = 'muranga:reseed {--media-path= : Root URL path for media assets}';
    protected $description = 'Reseed Murang\'a county with data from the SSSS location shoot (Aug 2026)';

    public function handle(MurangaAccurateDataSeeder $seeder): int
    {
        $this->info('=== Murang\'a County — Accurate Data Reseed ===');

        $county = County::where('slug', 'muranga')->first();
        if (!$county) {
            $this->error('County not found. Run CountySeeder first.');
            return 1;
        }

        $this->warn('Wiping old Murang\'a data...');
        $seeder->run();
        $this->info('Data reseeded successfully.');

        $this->updateCountyProfile($county);

        // Create/update MediaAsset for hero video slot
        $this->linkHeroVideo($county);

        // Create/update sector tile video links
        $this->linkSectorVideos($county);

        $this->info('');
        $this->info('Run the video pipeline to transcode footage:');
        $this->info('  sudo bash pipeline/muranga-video-process.sh');
        $this->info('');
        $this->info('After R2 upload, videos will render on the Murang\'a county page.');

        return 0;
    }

    private function updateCountyProfile(County $county): void
    {
        $county->update([
            'tagline' => 'The tea and coffee county — birthplace of Kenya\'s independence heroes',
            'description' => 'Murang\'a County is the heart of Kenya\'s Central Highlands, renowned for its premium tea and coffee production, stunning waterfalls, and deep historical significance as the cradle of the Kikuyu nation. The county features rolling tea plantations, the iconic Mukurwe wa Nyagathanga — the mythical Kikuyu origin site — dramatic gorges on the Maragua River, thrilling white-water rafting on the Sagana River, and a vibrant agricultural economy. Key attractions include Kanunga Falls, Twin Falls, the Murang\'a Gorges, Havila Island Resort, and the purple tea estates of Kangari.',
            'primary_sectors' => json_encode(['Agriculture', 'Tourism', 'Education', 'Healthcare']),
            'tourism_highlights' => json_encode([
                'Mukurwe wa Nyagathanga (Kikuyu origin site)',
                'Kanunga Falls',
                'Twin Falls (Maragua)',
                'Murang\'a Gorges',
                'Sagana River Rafting',
                'Tea Plantation Tours',
                'Havila Island Resort',
                'Purple Tea Estates',
            ]),
        ]);
        $this->info('County profile updated.');
    }

    private function linkHeroVideo(County $county): void
    {
        $path = $this->option('media-path') ?? 'muranga/video/hero/muranga-hero-compilation.mp4';
        $media = config('app.media_cdn_url', 'https://kicc-r2-media.techhubltd254.workers.dev/storage');

        MediaAsset::forSlot(County::class, $county->id, 'hero_video')->delete();

        $asset = MediaAsset::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'owner_id' => $county->id,
            'owner_type' => County::class,
            'slot' => 'hero_video',
            'disk' => 'r2',
            'path' => $path,
            'original_name' => 'muranga-hero-compilation.mp4',
            'mime' => 'video/mp4',
            'kind' => 'video',
            'size_bytes' => 0,
            'status' => 'ready',
            'alt_text' => 'Murang\'a County — Hero Compilation',
        ]);

        $asset->derivatives()->create([
            'kind' => 'video_mp4',
            'path' => $path,
            'mime' => 'video/mp4',
            'variant' => '1080p',
        ]);

        $this->info('Hero video MediaAsset created. Path: ' . $media . '/' . ltrim($path, '/'));
    }

    private function linkSectorVideos(County $county): void
    {
        $sectors = [
            'tourism' => 'tourism',
            'agriculture' => 'agriculture',
            'hospitality' => 'hospitality',
            'culture' => 'culture',
            'education' => 'education',
            'commerce' => 'commerce',
            'transport' => 'transport',
            'healthcare' => 'healthcare',
        ];

        $media = config('app.media_cdn_url', 'https://kicc-r2-media.techhubltd254.workers.dev/storage');

        foreach ($sectors as $slug => $sector) {
            $path = $this->option('media-path')
                ? "muranga/video/sectors/{$sector}.mp4"
                : "muranga/video/sectors/{$sector}.mp4";

            MediaAsset::forSlot(County::class, $county->id, "sector_video_{$slug}")->delete();

            $asset = MediaAsset::create([
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'owner_id' => $county->id,
                'owner_type' => County::class,
                'slot' => "sector_video_{$slug}",
                'disk' => 'r2',
                'path' => $path,
                'original_name' => "{$sector}.mp4",
                'mime' => 'video/mp4',
                'kind' => 'video',
                'size_bytes' => 0,
                'status' => 'ready',
                'alt_text' => "Murang'a {$sector} sector video",
            ]);

            $asset->derivatives()->create([
                'kind' => 'video_mp4',
                'path' => $path,
                'mime' => 'video/mp4',
                'variant' => '720p',
            ]);

            $this->info("  Sector {$sector}: MediaAsset created.");
        }
    }
}