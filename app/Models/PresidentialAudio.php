<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PresidentialAudio extends Model
{
    protected $table = 'presidential_audios';

    protected $fillable = [
        'title', 'slug', 'speaker', 'transcript',
        'audio_asset_id', 'key_topics', 'timemarks',
    ];

    protected function casts(): array
    {
        return [
            'key_topics' => 'array',
            'timemarks' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn ($m) => $m->slug ??= Str::slug($m->title) . '-' . strtolower(Str::random(4)));
    }

    public function audioAsset()
    {
        return $this->belongsTo(MediaAsset::class, 'audio_asset_id');
    }

    public function droneSequences()
    {
        return $this->hasMany(DroneSequence::class, 'audio_overlay_id');
    }
}