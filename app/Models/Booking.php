<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'booking_reference', 'user_id', 'exhibition_id', 'booking_type',
        'subtotal', 'tax', 'total', 'currency', 'status',
        'billing_info', 'notes',
        'paid_at', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
            'billing_info' => 'array',
            'notes' => 'array',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function exhibition()
    {
        return $this->belongsTo(Exhibition::class);
    }

    public function bookingBooths()
    {
        return $this->hasMany(BookingBooth::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
