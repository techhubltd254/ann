<?php

namespace App\Models\Travel;

use Illuminate\Database\Eloquent\Model;

class FlightBooking extends Model
{
    protected $table = 'flight_bookings';
    protected $guarded = [];
    protected $casts = ['booked_at' => 'datetime', 'cancelled_at' => 'datetime'];
}
