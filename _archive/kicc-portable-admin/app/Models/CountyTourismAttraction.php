<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToCounty;

class CountyTourismAttraction extends Model {

    use BelongsToCounty;
    protected $fillable = ['county_id', 'name', 'description', 'category', 'location', 'entry_fee', 'opening_hours', 'contact', 'latitude', 'longitude', 'is_published'];
    public function county() { return $this->belongsTo(County::class); }
}
