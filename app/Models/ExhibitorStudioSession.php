<?php
namespace App\Models;

use App\Models\Booth;
use App\Models\User;
use App\Models\BoothAuthorization;
use Illuminate\Database\Eloquent\Model;

class ExhibitorStudioSession extends Model
{
    protected $fillable = [
        'user_id', 'booth_id', 'booth_authorization_id',
        'session_token', 'stream_status',
        'session_started_at', 'last_activity_at', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'session_started_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function booth(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Booth::class);
    }

    public function authorization(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(BoothAuthorization::class, 'booth_authorization_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isLive(): bool
    {
        return $this->stream_status === 'live';
    }

    public function setStreamStatus(string $status): void
    {
        $this->update(['stream_status' => $status, 'last_activity_at' => now()]);
    }
}