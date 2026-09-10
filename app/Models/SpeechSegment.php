<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpeechSegment extends Model
{
    protected $fillable = [
        'voice_note_id', 'start_seconds', 'end_seconds',
        'text', 'confidence', 'speaker_label', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'start_seconds' => 'float',
            'end_seconds' => 'float',
            'confidence' => 'float',
            'metadata' => 'array',
        ];
    }

    public function voiceNote()
    {
        return $this->belongsTo(VoiceNote::class);
    }
}