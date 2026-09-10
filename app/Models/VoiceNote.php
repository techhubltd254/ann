<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoiceNote extends Model
{
    protected $fillable = [
        'title', 'audio_asset_id', 'entity_type', 'entity_id',
        'voice_type', 'transcript', 'metadata', 'is_published', 'transcribed_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'is_published' => 'boolean',
            'transcribed_at' => 'datetime',
        ];
    }

    public function audioAsset()
    {
        return $this->belongsTo(MediaAsset::class, 'audio_asset_id');
    }

    public function entity()
    {
        return $this->morphTo();
    }

    public function segments()
    {
        return $this->hasMany(SpeechSegment::class);
    }
}