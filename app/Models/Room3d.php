<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Room3d extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'description',
        'image_paths',
        'cover_image',
        'status',
        'pipeline',
        'job_result',
        'processed_at',
    ];

    protected $casts = [
        'image_paths' => 'array',
        'job_result' => 'array',
        'processed_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($room) {
            if (empty($room->slug)) {
                $room->slug = Str::slug($room->title) . '-' . Str::random(6);
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function images(): array
    {
        return $this->image_paths ?? [];
    }

    public function coverUrl(): ?string
    {
        if ($this->cover_image) {
            return url('storage/' . $this->cover_image);
        }
        $images = $this->images();
        return !empty($images) ? url('storage/' . $images[0]) : null;
    }

    public function isReady(): bool
    {
        return $this->status === 'ready' || $this->status === 'processed';
    }
}