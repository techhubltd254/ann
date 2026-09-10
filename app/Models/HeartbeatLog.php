<?php
namespace App\Models;

use App\Models\Booth;
use App\Models\BoothAuthorization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class HeartbeatLog extends Model
{
    protected $fillable = [
        'booth_authorization_id', 'booth_id', 'status',
        'session_id', 'payload', 'token_sent',
        'token_verified', 'heartbeat_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'heartbeat_at' => 'datetime',
        ];
    }

    public function boothAuthorization(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(BoothAuthorization::class);
    }

    public function booth(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Booth::class);
    }

    public function scopeFailed($query)
    {
        return $query->where('token_verified', 'fail');
    }

    public function scopeRecent($query, int $minutes = 10)
    {
        return $query->where('heartbeat_at', '>=', now()->subMinutes($minutes));
    }
}