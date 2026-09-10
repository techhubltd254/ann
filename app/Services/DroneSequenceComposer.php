<?php

namespace App\Services;

use App\Models\DroneSequence;
use App\Models\MediaAsset;

/**
 * DroneSequenceComposer — layers presidential audio over drone footage.
 * Uses ffmpeg to mix the drone video with the selected audio overlay.
 */
class DroneSequenceComposer
{
    public function forSequence(DroneSequence $sequence): array
    {
        $droneVideo = $sequence->droneVideo;
        $audio = $sequence->audioOverlay;

        return [
            'name' => $sequence->name,
            'location' => $sequence->location,
            'coordinates' => ['lat' => $sequence->latitude, 'lng' => $sequence->longitude],
            'drone' => [
                'mp4' => $droneVideo?->mp4Url() ?? $droneVideo?->url(),
                'hls' => $droneVideo?->derivativeUrl('hls_master'),
                'poster' => $droneVideo?->posterUrl(),
            ],
            'audio' => [
                'title' => $audio?->title,
                'speaker' => $audio?->speaker,
                'mp3' => $audio?->audioAsset?->mp4Url() ?? $audio?->audioAsset?->url(),
                'transcript' => $audio?->transcript,
                'key_topics' => $audio?->key_topics,
                'timemarks' => $audio?->timemarks,
            ],
        ];
    }

    /**
     * Layer the presidential audio over drone footage using ffmpeg.
     */
    public function compose(string $droneVideoPath, string $audioPath, string $outputPath = ''): ?string
    {
        $outputDir = storage_path('app/public/drone');
        if (!is_dir($outputDir)) mkdir($outputDir, 0755, true);

        $output = $outputPath ?: $outputDir . '/' . uniqid('drone_seq_', true) . '.mp4';

        $cmd = sprintf(
            'ffmpeg -y -i %s -i %s -c:v copy -c:a aac -shortest -map 0:v:0 -map 1:a:0 %s',
            escapeshellarg($droneVideoPath),
            escapeshellarg($audioPath),
            escapeshellarg($output)
        );

        exec($cmd . ' 2>/dev/null', $out, $code);

        return $code === 0 && file_exists($output) ? $output : null;
    }
}