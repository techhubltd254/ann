<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CountyTourismAttraction extends Model
{
    protected $fillable = ['county_id', 'name', 'description', 'category', 'image_url', 'location', 'entry_fee', 'opening_hours', 'contact', 'latitude', 'longitude', 'is_published'];
    
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'entry_fee' => 'decimal:2',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
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
