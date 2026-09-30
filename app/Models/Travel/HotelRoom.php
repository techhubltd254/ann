<?php

namespace App\Models\Travel;

use Illuminate\Database\Eloquent\Model;

/**
 * Hotel room inventory. Maps to `hotel_rooms` table.
 */
class HotelRoom extends Model
{
    protected $table = 'hotel_rooms';
    protected $fillable = [
        'hotel_id', 'name', 'room_type', 'price_per_night',
        'max_guests', 'description', 'amenities', 'images', 'is_active',
    ];
    protected $casts = ['amenities' => 'array', 'images' => 'array'];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }
}