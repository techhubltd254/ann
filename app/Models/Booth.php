<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booth extends Model
{
    protected $fillable = [
        'exhibition_id', 'booth_number', 'name', 'size', 'category',
        'description', 'amenities', 'price', 'discount_price',
        'max_quantity', 'booked_quantity', 'location_hint',
        'dimensions', 'images', 'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'discount_price' => 'decimal:2',
            'amenities' => 'array',
            'dimensions' => 'array',
            'images' => 'array',
        ];
    }

    public function exhibition()
    {
        return $this->belongsTo(Exhibition::class);
    }

    public function bookingBooths()
    {
        return $this->hasMany(BookingBooth::class);
    }
}
