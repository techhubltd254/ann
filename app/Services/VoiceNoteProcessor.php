<?php

namespace App\Services;

use App\Models\MediaAsset;
use App\Models\VoiceNote;
use App\Models\SpeechSegment;
use Illuminate\Support\Str;

/**
 * VoiceNoteProcessor — processes audio clips for citizen/patient testimonies.
 * Extracts metadata, stores as SpeakSegments, fires N8n events.
 */
class VoiceNoteProcessor
{
    public function process(VoiceNote $voiceNote): array
    {
        $audio = $voiceNote->audioAsset;

        return [
            'id' => $voiceNote->id,
            'title' => $voiceNote->title,
            'voice_type' => $voiceNote->voice_type,
            'is_published' => $voiceNote->is_published,
            'audio' => [
                'url' => $audio?->mp4Url() ?? $audio?->url(),
                'duration' => $voiceNote->metadata['duration'] ?? null,
            ],
            'transcript' => $voiceNote->transcript,
            'segments' => $voiceNote->segments->map(fn ($s) => [
                'start' => $s->start_seconds,
                'end' => $s->end_seconds,
                'text' => $s->text,
                'confidence' => $s->confidence,
                'speaker' => $s->speaker_label,
            ]),
            'entity' => [
                'type' => $voiceNote->entity_type,
                'id' => $voiceNote->entity_id,
            ],
        ];
    }

    /**
     * Create a VoiceNote from an uploaded audio file.
     */
    public function createFromUpload(
        string $entityType,
        int $entityId,
        string $title,
        string $voiceType,
        string $audioPath,
        string $disk = 'r2'
    ): VoiceNote {
        // Store as MediaAsset
        $asset = MediaAsset::create([
            'uuid' => (string) Str::uuid(),
            'owner_type' => $entityType,
            'owner_id' => $entityId,
            'slot' => 'voice_note',
            'disk' => $disk,
            'path' => $audioPath,
            'original_name' => basename($audioPath),
            'mime' => mime_content_type($audioPath) ?: 'audio/mpeg',
            'kind' => 'audio',
            'size_bytes' => filesize($audioPath),
            'status' => 'ready',
        ]);

        // Get audio duration via ffprobe
        $duration = $this->getAudioDuration($audioPath);

        return VoiceNote::create([
            'title' => $title,
            'audio_asset_id' => $asset->id,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'voice_type' => $voiceType,
            'metadata' => ['duration' => $duration, 'source' => 'upload'],
            'is_published' => false,
        ]);
    }

    /**
     * Mark a voice note as transcribed and attach timestamped segments.
     */
    public function attachTranscript(VoiceNote $voiceNote, string $transcript, array $segments = []): void
    {
        $voiceNote->update([
            'transcript' => $transcript,
            'transcribed_at' => now(),
            'is_published' => true,
        ]);

        foreach ($segments as $seg) {
            SpeechSegment::create([
                'voice_note_id' => $voiceNote->id,
                'start_seconds' => $seg['start'],
                'end_seconds' => $seg['end'],
                'text' => $seg['text'],
                'confidence' => $seg['confidence'] ?? null,
                'speaker_label' => $seg['speaker'] ?? null,
            ]);
        }

        \App\Services\N8nService::fire('voice_note_transcribed', [
            'voice_note_id' => $voiceNote->id,
            'title' => $voiceNote->title,
            'segments_count' => count($segments),
        ]);
    }

    protected function getAudioDuration(string $path): ?float
    {
        $cmd = sprintf('ffprobe -v error -show_entries format=duration -of csv=p=0 %s 2>/dev/null', escapeshellarg($path));
        $out = trim(shell_exec($cmd) ?? '');
        return is_numeric($out) ? (float) $out : null;
    }
}