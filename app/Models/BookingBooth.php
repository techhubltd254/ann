<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingBooth extends Model
{
    protected $fillable = [
        'booking_id', 'booth_id', 'price', 'discount',
        'exhibitor_name', 'exhibitor_email', 'exhibitor_phone',
        'requirements',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'discount' => 'decimal:2',
            'requirements' => 'array',
        ];
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function booth()
    {
        return $this->belongsTo(Booth::class);
    }
}
