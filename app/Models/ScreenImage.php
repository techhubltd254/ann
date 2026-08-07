<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScreenImage extends Model
{
    protected $table = 'screen_images';

    protected $fillable = [
        'filename', 'storage_path', 'original_path', 'county_id', 'sector_ids',
        'tags', 'quality_score', 'scene_type', 'width', 'height',
        'brightness', 'contrast', 'has_water', 'source',
    ];

    protected function casts(): array
    {
        return [
            'quality_score' => 'float',
            'width' => 'integer',
            'height' => 'integer',
            'brightness' => 'float',
            'contrast' => 'float',
            'has_water' => 'boolean',
        ];
    }
}
