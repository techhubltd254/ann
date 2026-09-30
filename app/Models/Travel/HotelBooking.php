<?php

namespace App\Models\Travel;

use Illuminate\Database\Eloquent\Model;

class HotelBooking extends Model
{
    protected $table = 'hotel_bookings';
    protected $fillable = [
        'booking_reference', 'user_id', 'hotel_id', 'room_id',
        'check_in', 'check_out', 'guest_count', 'subtotal', 'tax', 'total',
        'currency', 'status', 'cancellation_policy', 'booked_at', 'cancelled_at',
    ];
    protected $casts = ['check_in' => 'date', 'check_out' => 'date', 'booked_at' => 'datetime', 'cancelled_at' => 'datetime'];
}