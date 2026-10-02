<?php

namespace App\Models\Travel;

use Illuminate\Database\Eloquent\Model;

use App\Models\Travel\Airline;
use App\Models\Travel\Airport;

class Flight extends Model
{
    protected $fillable = [
        'airline_id', 'flight_number', 'origin_airport_id', 'destination_airport_id',
        'departure_time', 'arrival_time', 'duration_minutes', 'days_of_week',
        'aircraft_type', 'base_price', 'currency', 'status',
    ];
    protected $casts = [
        'departure_time' => 'datetime', 'arrival_time' => 'datetime',
        'days_of_week' => 'array',
    ];

    public function airline() { return $this->belongsTo(Airline::class); }
    public function origin() { return $this->belongsTo(Airport::class, 'origin_airport_id'); }
    public function destination() { return $this->belongsTo(Airport::class, 'destination_airport_id'); }
    public function inventory() { return $this->hasMany(FlightInventory::class); }
}
