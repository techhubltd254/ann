<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Exhibition extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'tagline', 'county_id', 'venue_id',
        'start_date', 'end_date', 'open_time', 'close_time',
        'cover_image', 'gallery', 'status', 'is_featured',
        'organizer_info', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'open_time' => 'datetime',
            'close_time' => 'datetime',
            'is_featured' => 'boolean',
            'gallery' => 'array',
            'organizer_info' => 'array',
            'meta' => 'array',
        ];
    }

    public function county()
    {
        return $this->belongsTo(County::class);
    }

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    public function sessions()
    {
        return $this->hasMany(EventSession::class);
    }

    public function booths()
    {
        return $this->hasMany(Booth::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function ticketTypes()
    {
        return $this->hasMany(TicketType::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function booted(): void
    {
        static::creating(function (Exhibition $exhibition) {
            if (empty($exhibition->slug)) {
                $exhibition->slug = Str::slug($exhibition->name);
            }
        });
    }
}
