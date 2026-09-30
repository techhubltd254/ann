<?php

namespace App\Models\Travel;

use Illuminate\Database\Eloquent\Model;

/**
 * Flight inventory — available seats/classes for a flight on a specific date.
 * Maps to the `flight_inventory` table (no Laravel snake_case convention mismatch).
 */
class FlightInventory extends Model
{
    protected $table = 'flight_inventory';
    protected $fillable = [
        'flight_id', 'date', 'fare_class', 'price', 'available_seats',
        'is_active', 'departure_time', 'arrival_time',
    ];
    protected $casts = ['date' => 'date', 'departure_time' => 'datetime', 'arrival_time' => 'datetime'];

    public function flight()
    {
        return $this->belongsTo(Flight::class);
    }
}