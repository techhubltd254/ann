<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Venue extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'venue_type',
        'address', 'city', 'county', 'latitude', 'longitude',
        'capacity', 'amenities', 'contact_info', 'cover_image', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:6',
            'longitude' => 'decimal:6',
            'capacity' => 'integer',
            'amenities' => 'array',
            'contact_info' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function exhibitions()
    {
        return $this->hasMany(Exhibition::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function booted(): void
    {
        static::creating(function (Venue $venue) {
            if (empty($venue->slug)) {
                $venue->slug = Str::slug($venue->name);
            }
        });
    }
}
