<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class VenueBooking extends Model
{
    protected $table = 'venue_bookings';

    protected $fillable = [
        'venue_id', 'user_id', 'booking_reference', 'status',
        'event_type', 'event_date', 'event_end_date',
        'expected_guests', 'expected_cars', 'security_level',
        'additional_facilities', 'catering_requirements', 'av_requirements',
        'preferred_layout', 'requires_live_coverage', 'requires_ad_screens',
        'requires_fountains', 'special_requests', 'estimated_budget',
        'deposit_amount', 'total_quote', 'deposit_paid_at', 'confirmed_at',
    ];

    protected $casts = [
        'event_date' => 'date',
        'event_end_date' => 'date',
        'expected_guests' => 'integer',
        'expected_cars' => 'integer',
        'estimated_budget' => 'float',
        'deposit_amount' => 'float',
        'total_quote' => 'float',
        'deposit_paid_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'requires_live_coverage' => 'boolean',
        'requires_ad_screens' => 'boolean',
        'requires_fountains' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $booking) {
            $booking->booking_reference ??= 'KICC-VNU-' . strtoupper(Str::random(8));
            $booking->status ??= 'reserved';
        });
    }

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
