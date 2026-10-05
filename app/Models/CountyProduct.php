<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CountyProduct extends Model
{
    protected $fillable = ['county_id', 'countyId', 'user_id', 'userId', 'name', 'description', 'category', 'image_url', 'imageUrl', 'video_url', 'videoUrl', 'videos', 'price', 'unit', 'booking_type', 'bookingType', 'status', 'is_published', 'isPublished'];
    protected $casts = ['videos' => 'array', 'is_published' => 'boolean', 'price' => 'float'];
    public function county() { return $this->belongsTo(County::class); }
    public function user() { return $this->belongsTo(User::class); }

    protected static function booted(): void
    {
        static::creating(function (self $m) {
            $camelMap = [
                'county_id' => 'countyId', 'user_id' => 'userId',
                'image_url' => 'imageUrl', 'video_url' => 'videoUrl',
                'booking_type' => 'bookingType', 'is_published' => 'isPublished',
            ];
            foreach ($camelMap as $snake => $camel) {
                if ($m->getAttribute($camel) === null && $m->getAttribute($snake) !== null) {
                    $m->setAttribute($camel, $m->getAttribute($snake));
                }
            }
        });
    }
}
