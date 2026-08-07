<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = [
        'ticket_code', 'booking_id', 'ticket_type_id', 'user_id',
        'price', 'status', 'qr_code',
        'holder_name', 'holder_email',
        'checked_in_at', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'check_in_data' => 'array',
            'checked_in_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function ticketType()
    {
        return $this->belongsTo(TicketType::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function exhibition()
    {
        return $this->hasOneThrough(Exhibition::class, Booking::class, 'id', 'id', 'booking_id', 'exhibition_id');
    }
}
