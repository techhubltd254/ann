<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CountyHotel extends Model
{
    protected $fillable = ['county_id', 'name', 'category', 'star_rating', 'description', 'location', 'phone', 'email', 'website', 'latitude', 'longitude', 'price_range_min', 'price_range_max', 'amenities', 'is_published'];
    protected function casts(): array { return ['amenities' => 'json']; }
    public function county() { return $this->belongsTo(County::class); }
}
