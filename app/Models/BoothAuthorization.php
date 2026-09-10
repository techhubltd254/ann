<?php
namespace App\Models;

use App\Models\Booth;
use App\Models\User;
use App\Models\HeartbeatLog;
use App\Models\ExhibitorStudioSession;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BoothAuthorization extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'booth_id', 'user_id', 'status',
        'api_key', 'api_key_expires_at',
        'reason_code', 'reason_note',
        'authorized_at', 'terminated_at',
        'last_heartbeat_at', 'missed_heartbeats',
    ];

    protected function casts(): array
    {
        return [
            'api_key_expires_at' => 'datetime',
            'authorized_at' => 'datetime',
            'terminated_at' => 'datetime',
            'last_heartbeat_at' => 'datetime',
            'missed_heartbeats' => 'integer',
        ];
    }

    public function booth(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Booth::class);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function heartbeatLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(HeartbeatLog::class);
    }

    public function studioSessions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ExhibitorStudioSession::class);
    }

    public function scopeAuthorized($query)
    {
        return $query->where('status', 'AUTHORIZED');
    }

    public function isAuthorized(): bool
    {
        return $this->status === 'AUTHORIZED';
    }

    public function authorize(): void
    {
        $this->update([
            'status' => 'AUTHORIZED',
            'authorized_at' => now(),
            'missed_heartbeats' => 0,
        ]);
    }

    public function terminate(string $reasonCode, ?string $note = null): void
    {
        $this->update([
            'status' => 'STOPPED',
            'terminated_at' => now(),
            'reason_code' => $reasonCode,
            'reason_note' => $note,
        ]);
    }

    public function recordHeartbeat(): void
    {
        $this->update([
            'last_heartbeat_at' => now(),
            'missed_heartbeats' => 0,
        ]);
    }

    public function incrementMissedHeartbeats(): void
    {
        $this->increment('missed_heartbeats');
    }

    public function missedHeartbeatThresholdReached(): bool
    {
        return $this->missed_heartbeats >= 2;
    }
}