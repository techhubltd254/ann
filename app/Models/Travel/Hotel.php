<?php

namespace App\Models\Travel;

use App\Models\County;
use Illuminate\Database\Eloquent\Model;

use App\Models\Travel\HotelRoom;

class Hotel extends Model { protected $fillable = ['county_id', 'name', 'slug', 'description', 'star_rating', 'address', 'latitude', 'longitude', 'phone', 'email', 'website', 'check_in_time', 'check_out_time', 'amenities', 'policies', 'images', 'is_active']; public function county() { return $this->belongsTo(County::class); } }
