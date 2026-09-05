<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CountyHealthFacility extends Model
{
    protected $fillable = ['county_id', 'name', 'type', 'level', 'description', 'location', 'phone', 'email', 'services', 'is_published'];
    
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }
public function county() { return $this->belongsTo(County::class); }

    protected static function booted(): void
    {
        static::creating(function (self $m) {
            $m->countyId ??= $m->county_id;
        });
    }
}
