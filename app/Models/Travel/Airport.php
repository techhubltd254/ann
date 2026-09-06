<?php

namespace App\Models\Travel;

use Illuminate\Database\Eloquent\Model;

class Airport extends Model { protected $fillable = ['name', 'iata_code', 'icao_code', 'city', 'county_id', 'country', 'latitude', 'longitude', 'altitude_ft', 'timezone', 'is_international', 'is_active']; }
