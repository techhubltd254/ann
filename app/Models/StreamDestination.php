<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StreamDestination extends Model
{
    protected $fillable = [
        'live_stream_id', 'destinable_id', 'destinable_type',
        'status', 'started_at', 'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function liveStream()
    {
        return $this->belongsTo(LiveStream::class);
    }

    public function destinable()
    {
        return $this->morphTo();
    }
}