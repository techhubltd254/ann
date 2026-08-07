<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CountyCultureSite extends Model
{
    protected $fillable = ['county_id', 'name', 'type', 'description', 'location', 'community', 'contact', 'latitude', 'longitude', 'is_published'];
    public function county() { return $this->belongsTo(County::class); }
}
