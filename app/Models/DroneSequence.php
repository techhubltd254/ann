<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DroneSequence extends Model
{
    protected $fillable = [
        'name', 'slug', 'county_id', 'location',
        'latitude', 'longitude', 'drone_video_id', 'audio_overlay_id', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_published' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn ($m) => $m->slug ??= Str::slug($m->name) . '-' . strtolower(Str::random(4)));
    }

    public function county()
    {
        return $this->belongsTo(County::class);
    }

    public function droneVideo()
    {
        return $this->belongsTo(MediaAsset::class, 'drone_video_id');
    }

    public function audioOverlay()
    {
        return $this->belongsTo(PresidentialAudio::class, 'audio_overlay_id');
    }
}