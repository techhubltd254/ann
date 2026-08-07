<?php

namespace App\Console\Commands;

use App\Models\Screen;
use App\Models\ScreenImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class GenerateScreenVideo extends Command
{
    protected $signature = 'screen:generate-video
                            {screen_id : The screen ID (e.g. county_main_14, sector_tourism)}
                            {--queue : Dispatch to the pipeline queue instead of running sync}';

    protected $description = 'Generate a showcase video for a screen from its approved images';

    public function handle(): int
    {
        $screen = Screen::find($this->argument('screen_id'));
        if (!$screen) {
            $this->error("Screen '{$screen_id}' not found");
            return 1;
        }

        $this->info("Generating video for screen: {$screen->id} ({$screen->label})");

        // Fetch approved images for this screen's entity
        $images = $this->fetchImages($screen);
        if ($images->isEmpty()) {
            $this->info("No images found for this screen yet — register images and re-run");
            return 0;
        }
        $this->info("Found {$images->count()} images");

        // Build the pipeline input
        $preset = $this->resolvePreset($screen);
        $outputDir = $this->outputDir($screen);

        // Call the Python pipeline via the MCP server or direct process
        if ($this->option('queue')) {
            // Dispatch to queue — pipeline worker picks it up
            \App\Jobs\RunPipelineJob::dispatch('screen', $screen->id);
            $this->info('Queued');
        } else {
            $this->runPipeline($screen, $images, $preset, $outputDir);
        }

        return 0;
    }

    private function fetchImages(Screen $screen): \Illuminate\Support\Collection
    {
        $query = ScreenImage::where('county_id', $screen->county_id);

        if ($screen->sector_id) {
            $query->where('sector_ids', 'like', "%{$screen->sector_id}%");
        }

        return $query->orderByDesc('quality_score')
                     ->take($screen->max_images ?? 30)
                     ->get();
    }

    private function resolvePreset(Screen $screen): string
    {
        return match (true) {
            str_starts_with($screen->id, 'county_main') => 'county_main',
            str_starts_with($screen->id, 'county_sub')  => 'county_sub',
            str_starts_with($screen->id, 'sector')      => 'sector_pavilion',
            str_starts_with($screen->id, 'hero')        => 'hero_wall',
            str_starts_with($screen->id, 'booth')       => 'booth',
            default => 'county_sub',
        };
    }

    private function outputDir(Screen $screen): string
    {
        $path = "screens/auto_{$screen->id}";
        Storage::disk('public')->makeDirectory(dirname($path));
        return Storage::disk('public')->path($path);
    }

    private function runPipeline(Screen $screen, $images, string $preset, string $outputDir): void
    {
        $imagePaths = $images->pluck('storage_path')->toArray();
        $imagePathsJson = json_encode($imagePaths);
        $countyName = $screen->county?->name ?? 'Kenya';

        // Call the Python auto_pipeline
        $script = base_path('kenya-3d-platform/pipeline/auto_pipeline.py');
        $cmd = [
            'python3', $script,
            '--screen-id', $screen->id,
            '--county', $countyName,
            '--images-json', $imagePathsJson,
            '--preset', $preset,
            '--output', $outputDir,
        ];

        $process = new Process($cmd);
        $process->setTimeout(600);
        $process->run();

        if (!$process->isSuccessful()) {
            $this->error("Pipeline failed: {$process->getErrorOutput()}");
            return;
        }

        $this->info($process->getOutput());
        $this->info("Video generated for screen {$screen->id}");

        // Update screen timestamp to signal frontend to reload video
        $screen->touch();
    }
}