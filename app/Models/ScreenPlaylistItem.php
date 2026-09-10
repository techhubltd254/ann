<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScreenPlaylistItem extends Model
{
    protected $fillable = [
        'screen_id', 'media_asset_id', 'content_type',
        'sort_order', 'duration_seconds', 'is_active', 'overlay_data',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'duration_seconds' => 'integer',
            'overlay_data' => 'array',
        ];
    }

    public function screen()
    {
        return $this->belongsTo(Screen::class);
    }

    public function mediaAsset()
    {
        return $this->belongsTo(MediaAsset::class);
    }
}