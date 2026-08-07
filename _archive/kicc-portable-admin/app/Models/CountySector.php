<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToCounty;

class CountySector extends Model
{
    use BelongsToCounty;

    protected $fillable = [
        'county_id', 'name', 'slug', 'display_order', 'is_active',
        'local_id', 'sync_status', 'content_hash',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function tiles()
    {
        return $this->hasMany(SectorTile::class, 'county_sector_id')->orderBy('display_order');
    }

    public function activeTiles()
    {
        return $this->tiles()->where('is_active', true);
    }
}
