<?php

namespace App\Models\Travel;

use Illuminate\Database\Eloquent\Model;

class TransferBooking extends Model
{
    protected $table = 'transfer_bookings';
    protected $fillable = [
        'booking_reference', 'user_id', 'transfer_id', 'pickup_location',
        'dropoff_location', 'passenger_count', 'subtotal', 'tax', 'total',
        'currency', 'status', 'pickup_time', 'booked_at', 'cancelled_at',
    ];
    protected $casts = ['pickup_time' => 'datetime', 'booked_at' => 'datetime', 'cancelled_at' => 'datetime'];
}