<?php

namespace App\Models\Travel;

use Illuminate\Database\Eloquent\Model;

class Airline extends Model { protected $fillable = ['name', 'iata_code', 'icao_code', 'country', 'logo_url', 'website', 'is_active']; }
