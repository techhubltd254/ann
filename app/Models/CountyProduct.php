<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CountyProduct extends Model
{
    protected $fillable = ['county_id', 'user_id', 'name', 'description', 'category', 'image_url', 'video_url', 'videos', 'price', 'unit', 'booking_type', 'status', 'is_published'];
    protected $casts = ['videos' => 'array', 'is_published' => 'boolean', 'price' => 'float'];
    public function county() { return $this->belongsTo(County::class); }
    public function user() { return $this->belongsTo(User::class); }

    protected static function booted(): void
    {
        static::creating(function (self $m) {
        });
    }
}
