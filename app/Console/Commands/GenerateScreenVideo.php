<?php

namespace App\Console\Commands;

use App\Models\Screen;
use App\Services\VideoService;
use Illuminate\Console\Command;

class GenerateScreenVideo extends Command
{
    protected $signature = 'screen:generate
        {id? : Screen ID (e.g. county_main_14). Generates all screens if omitted}
        {--force : Regenerate even if video exists}';

    protected $description = 'Generate showcase videos for exhibition screens';

    public function handle(VideoService $video): int
    {
        $screenId = $this->argument('id');

        if ($screenId) {
            return $this->generateOne($video, $screenId);
        }

        $screens = Screen::where('active', true)->orderBy('id')->get();
        if ($screens->isEmpty()) {
            $this->warn('No screens found. Run `php artisan db:seed --class=ScreenSeeder` first.');
            return Command::FAILURE;
        }

        $this->info("Generating videos for {$screens->count()} screens...");

        $success = 0;
        $fail = 0;
        foreach ($screens as $screen) {
            $outPath = storage_path("app/public/screens/auto_{$screen->id}.mp4");
            if (file_exists($outPath) && !$this->option('force')) {
                $this->line("  SKIP {$screen->id} (exists)");
                $success++;
                continue;
            }

            $this->line("  GEN  {$screen->id} ({$screen->label})...");
            try {
                $path = $video->generateForScreen($screen->id);
                $size = file_exists($path) ? round(filesize($path) / 1024 / 1024, 1) : 0;
                $this->line("       Done ({$size} MB)");
                $success++;
            } catch (\Throwable $e) {
                $this->error("       Failed: {$e->getMessage()}");
                $fail++;
            }
        }

        $this->newLine();
        $this->info("Done: {$success} generated, {$fail} failed.");

        return $fail > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    protected function generateOne(VideoService $video, string $screenId): int
    {
        $screen = Screen::find($screenId);
        if (!$screen) {
            $this->error("Screen '{$screenId}' not found.");
            return Command::FAILURE;
        }

        $outPath = storage_path("app/public/screens/auto_{$screenId}.mp4");
        if (file_exists($outPath) && !$this->option('force')) {
            $this->warn("Video already exists at {$outPath}. Use --force to regenerate.");
            return Command::SUCCESS;
        }

        $this->info("Generating video for {$screen->label} ({$screenId})...");
        try {
            $path = $video->generateForScreen($screenId);
            $size = file_exists($path) ? round(filesize($path) / 1024 / 1024, 1) : 0;
            $this->info("Done: {$path} ({$size} MB)");
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Failed: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}
