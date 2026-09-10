<?php

namespace App\Models;

use App\Models\Exhibition;
use App\Models\County;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class LiveStream extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'viewer_count' => 'integer',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'scheduled_start_at' => 'datetime',
            'scheduled_end_at' => 'datetime',
        ];
    }

    public function exhibition()
    {
        return $this->belongsTo(Exhibition::class);
    }

    public function county()
    {
        return $this->belongsTo(County::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isLive(): bool
    {
        return $this->status === 'live';
    }

    public function scopeLive($q)
    {
        return $q->where('status', 'live');
    }

    public function scopeIdle($q)
    {
        return $q->where('status', 'idle');
    }

    public function formattedViewerCount(): string
    {
        if ($this->viewer_count >= 1000) {
            return round($this->viewer_count / 1000, 1) . 'K';
        }
        return (string) $this->viewer_count;
    }
}