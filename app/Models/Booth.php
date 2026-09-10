<?php
namespace App\Models;

use App\Models\Exhibition;
use App\Models\County;
use App\Models\User;
use App\Models\LiveStream;
use App\Models\MeetingBooking;
use App\Models\FavouriteBooth;
use App\Models\HeartbeatLog;
use App\Models\BoothAuthorization;
use App\Models\ExhibitorStudioSession;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Booth extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'exhibition_id', 'county_id', 'user_id',
        'name', 'slug', 'description', 'tagline',
        'cover_image', 'thumbnail', 'gallery',
        'contact_phone', 'contact_email', 'whatsapp',
        'gps_lat', 'gps_lng', 'physical_address',
        'social_links', 'collateral',
        'status', 'type', 'max_viewers',
        'stream_status', 'meeting_slots',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'gps_lat' => 'float',
            'gps_lng' => 'float',
            'social_links' => 'array',
            'collateral' => 'array',
            'gallery' => 'array',
            'meta' => 'array',
            'meeting_slots' => 'array',
            'max_viewers' => 'integer',
        ];
    }

    public function exhibition(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Exhibition::class);
    }

    public function county(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(County::class);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function liveStreams(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LiveStream::class);
    }

    public function authorization(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(BoothAuthorization::class)->latestOfMany();
    }

    public function authorizations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BoothAuthorization::class);
    }

    public function heartbeatLogs(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(HeartbeatLog::class, BoothAuthorization::class);
    }

    public function studioSessions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ExhibitorStudioSession::class);
    }

    public function meetingBookings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MeetingBooking::class);
    }

    public function favourites(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(FavouriteBooth::class);
    }

    public function scopeLive($query)
    {
        return $query->where('stream_status', 'live');
    }

    public function scopeAuthorized($query)
    {
        return $query->whereHas('authorization', fn($q) => $q->where('status', 'AUTHORIZED'));
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function isLive(): bool
    {
        return $this->stream_status === 'live';
    }

    public function isAuthorized(): bool
    {
        return $this->authorization?->status === 'AUTHORIZED';
    }
}