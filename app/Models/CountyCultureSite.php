<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CountyCultureSite extends Model
{
    protected $fillable = ['county_id', 'name', 'type', 'description', 'location', 'community', 'contact', 'latitude', 'longitude', 'is_published'];
    
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }
public function county() { return $this->belongsTo(County::class); }

    protected static function booted(): void
    {
        static::creating(function (self $m) {
        });
    }
}
