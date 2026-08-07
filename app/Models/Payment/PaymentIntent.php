<?php

namespace App\Models\Payment;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PaymentIntent extends Model
{
    protected $guarded = [];
    protected $casts = [
        'amount' => 'float', 'confirmed_at' => 'datetime',
        'failed_at' => 'datetime', 'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(fn (self $pi) => $pi->intent_id ??= 'KICC-PI-' . strtoupper(\Illuminate\Support\Str::random(12)));
    }

    public function user() { return $this->belongsTo(User::class); }
    public function gateway() { return $this->belongsTo(Gateway::class, 'gateway_id'); }
    public function logs() { return $this->hasMany(TransactionLog::class);
    }

    // Polymorphic reference to any billable entity (Order, Booking, EscrowTransaction, etc.)
    public function reference() { return $this->morphTo(); }

    public function scopePending($q) { return $q->where('status', 'pending'); }
    public function scopeForUser($q, User $user) { return $q->where('user_id', $user->id); }

    public static function createFor(Model $billable, float $amount, array $meta = []): self
    {
        return self::create([
            'user_id' => $billable->user_id ?? auth()->id() ?? User::where('email', 'guest@kicc.go.ke')->value('id'),
            'reference_type' => $billable->getMorphClass(),
            'reference_id' => $billable->id,
            'amount' => $amount,
            'currency' => 'KES',
            'description' => $meta['description'] ?? null,
            'metadata' => $meta,
        ]);
    }

    public function confirm(): void { $this->update(['status' => 'confirmed', 'confirmed_at' => now()]); }
    public function markFailed(string $reason = null): void { $this->update(['status' => 'failed', 'failed_at' => now(), 'failure_reason' => $reason]); }
    public function refund(): void { $this->update(['status' => 'refunded']); }
}
