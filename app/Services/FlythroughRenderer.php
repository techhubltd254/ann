<?php

namespace App\Services;

use App\Models\HousingProject;
use App\Models\MediaAsset;

/**
 * FlythroughRenderer — generates 4D flythrough videos for housing projects.
 * Uses the existing MediaAsset pipeline to compose beneficiary audio + splat renders.
 */
class FlythroughRenderer
{
    public function forProject(HousingProject $project): array
    {
        $flythrough = $project->flythroughVideo;
        $splat = $project->splatAsset;

        return [
            'project' => $project->name,
            'type' => $project->project_type,
            'units' => ['total' => $project->total_units, 'completed' => $project->completed_units],
            'flythrough' => [
                'mp4' => $flythrough?->mp4Url() ?? $flythrough?->url(),
                'hls' => $flythrough?->derivativeUrl('hls_master'),
                'poster' => $flythrough?->posterUrl(),
            ],
            'splat' => [
                'url' => $splat?->splatUrl(),
                'glb' => $splat?->glbUrl(),
            ],
            'beneficiary_audio_ids' => $project->beneficiary_audio_ids,
            'location' => [
                'lat' => $project->latitude,
                'lng' => $project->longitude,
                'name' => $project->location,
            ],
        ];
    }

    /**
     * Create a flythrough MediaAsset from an uploaded video + audio mix.
     * Uses ffmpeg to mix the video with beneficiary audio overlay.
     */
    public function compose(string $videoPath, string $audioPath, string $outputPath): ?string
    {
        $outputDir = storage_path('app/public/flythroughs');
        if (!is_dir($outputDir)) mkdir($outputDir, 0755, true);

        $output = $outputPath ?: $outputDir . '/' . uniqid('flythrough_', true) . '.mp4';

        $cmd = sprintf(
            'ffmpeg -y -i %s -i %s -c:v copy -c:a aac -shortest -map 0:v:0 -map 1:a:0 %s',
            escapeshellarg($videoPath),
            escapeshellarg($audioPath),
            escapeshellarg($output)
        );

        exec($cmd . ' 2>/dev/null', $out, $code);

        return $code === 0 && file_exists($output) ? $output : null;
    }
}