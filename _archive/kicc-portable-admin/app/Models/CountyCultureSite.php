<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToCounty;

class CountyCultureSite extends Model {

    use BelongsToCounty;
    protected $fillable = ['county_id', 'name', 'type', 'description', 'location', 'community', 'contact', 'latitude', 'longitude', 'is_published'];
    public function county() { return $this->belongsTo(County::class); }
}
