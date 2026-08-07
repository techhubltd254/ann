<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToCounty;

class SectorTile extends Model
{
    use BelongsToCounty;

    protected $fillable = [
        'county_sector_id', 'title', 'tile_type', 'display_order',
        'content', 'is_active', 'local_id', 'sync_status', 'content_hash',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function sector()
    {
        return $this->belongsTo(CountySector::class, 'county_sector_id');
    }

    public function media()
    {
        return $this->hasMany(TileMedia::class, 'sector_tile_id');
    }

    public function primaryMedia()
    {
        return $this->media()->where('is_primary', true)->first();
    }
}
