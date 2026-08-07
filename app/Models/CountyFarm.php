<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CountyFarm extends Model
{
    protected $fillable = ['county_id', 'name', 'type', 'description', 'location', 'contact', 'size_acres', 'main_crops', 'products', 'is_published'];
    public function county() { return $this->belongsTo(County::class); }
}
