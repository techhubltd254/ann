<?php

namespace App\Models\Travel;

use Illuminate\Database\Eloquent\Model;

class Flight extends Model { protected $fillable = ['airline_id', 'flight_number', 'origin_airport_id', 'destination_airport_id', 'departure_time', 'arrival_time', 'duration_minutes', 'days_of_week', 'aircraft_type', 'base_price', 'currency', 'status']; }
