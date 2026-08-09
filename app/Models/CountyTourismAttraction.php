<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CountyTourismAttraction extends Model
{
    protected $fillable = ['county_id', 'name', 'description', 'category', 'image_url', 'location', 'entry_fee', 'opening_hours', 'contact', 'latitude', 'longitude', 'is_published'];
    public function county() { return $this->belongsTo(County::class); }
}
