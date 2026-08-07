<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CountyHealthFacility extends Model
{
    protected $fillable = ['county_id', 'name', 'type', 'level', 'description', 'location', 'phone', 'email', 'services', 'is_published'];
    public function county() { return $this->belongsTo(County::class); }
}
