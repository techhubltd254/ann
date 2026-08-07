<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventSession extends Model
{
    protected $fillable = [
        'exhibition_id', 'name', 'slug', 'description', 'session_type',
        'speaker', 'speaker_title', 'speaker_photo',
        'start_time', 'end_time', 'location', 'max_attendees',
        'cover_image', 'status',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'max_attendees' => 'integer',
        ];
    }

    public function exhibition()
    {
        return $this->belongsTo(Exhibition::class);
    }
}
