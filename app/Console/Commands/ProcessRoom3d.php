<?php

namespace App\Console\Commands;

use App\Models\Room3d;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ProcessRoom3d extends Command
{
    protected $signature = 'room3d:process {id}';
    protected $description = 'Process room photos into a 3D experience';

    public function handle()
    {
        $room = Room3d::findOrFail($this->argument('id'));
        $this->info("Processing room: {$room->title}");

        $room->update(['status' => 'processing']);

        try {
            $images = $room->images();
            if (empty($images)) {
                throw new \Exception('No images to process');
            }

            $imagePaths = array_map(fn($p) => Storage::path($p), $images);

            $result = [
                'image_count' => count($imagePaths),
                'processed_at' => now()->toIso8601String(),
                'pipeline' => $room->pipeline,
                'formats' => [],
            ];

            if ($room->pipeline === 'video_showcase') {
                $outputName = "room3d_{$room->id}_showcase.mp4";
                $outputPath = storage_path("app/public/screens/{$outputName}");

                $script = base_path('kenya-3d-platform/scripts/generate_showcase.php');
                if (file_exists($script)) {
                    $cmd = sprintf(
                        'python3 %s --images %s --duration 4 --label auto --output %s 2>&1',
                        escapeshellarg(base_path('kenya-3d-platform/scripts/generate_showcase.py')),
                        implode(' ', array_map('escapeshellarg', $imagePaths)),
                        escapeshellarg($outputPath)
                    );
                    $this->info("Running: {$cmd}");
                    exec($cmd, $output, $exitCode);

                    if ($exitCode === 0 && file_exists($outputPath)) {
                        $result['formats'][] = [
                            'type' => 'video',
                            'path' => "screens/{$outputName}",
                            'url' => url("storage/screens/{$outputName}"),
                        ];
                    }
                }
            }

            if (!empty($room->cover_image) && file_exists(Storage::path($room->cover_image))) {
            } elseif (!empty($images)) {
                $room->update(['cover_image' => $images[0]]);
            }

            $result['status'] = 'complete';
            $room->update([
                'status' => 'ready',
                'job_result' => $result,
                'processed_at' => now(),
            ]);

            $this->info("Room '{$room->title}' processed successfully");
            $this->info("View at: " . route('room3d.viewer', $room));

        } catch (\Exception $e) {
            $room->update([
                'status' => 'failed',
                'job_result' => ['error' => $e->getMessage()],
            ]);
            $this->error("Failed: {$e->getMessage()}");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}