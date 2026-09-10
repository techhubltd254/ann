<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class HousingProject extends Model
{
    protected $fillable = [
        'name', 'slug', 'county_id', 'location', 'latitude', 'longitude',
        'project_type', 'total_units', 'completed_units',
        'flythrough_video_id', 'splat_asset_id', 'beneficiary_audio_ids',
        'amenities', 'description', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'total_units' => 'integer',
            'completed_units' => 'integer',
            'beneficiary_audio_ids' => 'array',
            'amenities' => 'array',
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

    public function flythroughVideo()
    {
        return $this->belongsTo(MediaAsset::class, 'flythrough_video_id');
    }

    public function splatAsset()
    {
        return $this->belongsTo(MediaAsset::class, 'splat_asset_id');
    }
}