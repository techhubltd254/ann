<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TileMedia extends Model
{
    protected $fillable = [
        'sector_tile_id', 'media_type', 'file_path', 'r2_url',
        'resolution_tag', 'duration_seconds', 'is_primary',
        'local_id', 'content_hash',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'duration_seconds' => 'integer',
        ];
    }

    public function tile()
    {
        return $this->belongsTo(SectorTile::class, 'sector_tile_id');
    }
}
