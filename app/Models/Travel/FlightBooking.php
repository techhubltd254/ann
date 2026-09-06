<?php

namespace App\Models\Travel;

use Illuminate\Database\Eloquent\Model;

class FlightBooking extends Model
{
    protected $table = 'flight_bookings';
    protected $fillable = [
        'booking_reference', 'user_id', 'flight_id', 'flight_inventory_id',
        'fare_class', 'passenger_count', 'subtotal', 'tax', 'total',
        'currency', 'status', 'pnr_code', 'cancellation_policy',
        'booked_at', 'cancelled_at',
    ];
    protected $casts = ['booked_at' => 'datetime', 'cancelled_at' => 'datetime'];
}
