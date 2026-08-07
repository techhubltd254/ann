<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToCounty;

class CountyFarm extends Model {

    use BelongsToCounty;
    protected $fillable = ['county_id', 'name', 'type', 'description', 'location', 'contact', 'size_acres', 'main_crops', 'products', 'is_published'];
    public function county() { return $this->belongsTo(County::class); }
}
