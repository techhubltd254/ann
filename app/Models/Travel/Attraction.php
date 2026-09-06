<?php

namespace App\Models\Travel;

use App\Models\County;
use Illuminate\Database\Eloquent\Model;

class Attraction extends Model { protected $fillable = ['county_id', 'name', 'slug', 'description', 'category', 'address', 'latitude', 'longitude', 'entry_fee', 'currency', 'opening_hours', 'contact_phone', 'contact_email', 'website', 'images', 'is_active']; public function county() { return $this->belongsTo(County::class); } }
