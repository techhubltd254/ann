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
        'contact_name', 'contact_mobile', 'contact_whatsapp', 'contact_email', 'department_lead',
        'trader_type', 'is_verified_trader', 'spotlight_video_id',
        'floor_plan_id', 'position_x', 'position_y', 'position_z',
        'virtual_tour_url', 'interactive_assets',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'discount_price' => 'decimal:2',
            'amenities' => 'array',
            'dimensions' => 'array',
            'images' => 'array',
            'interactive_assets' => 'array',
            'is_verified_trader' => 'boolean',
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
