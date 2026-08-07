<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImmersiveContentRecord extends Model
{
    protected $fillable = ['sector_entity_id', 'county_id', 'title', 'tier', 'content_type', 'file_url', 'preview_url', 'metadata', 'is_published'];
    protected function casts(): array { return ['metadata' => 'json', 'is_published' => 'boolean']; }
    public function sectorEntity() { return $this->belongsTo(SectorEntity::class); }
    public function county() { return $this->belongsTo(County::class); }
}
