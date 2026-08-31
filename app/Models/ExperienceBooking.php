<?php

namespace App\Models;

use App\Models\County;
use App\Models\User;
use App\Models\Marketplace\Product;
use App\Models\Marketplace\OrderItem;
use Illuminate\Database\Eloquent\Model;

class ExperienceBooking extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'transport_out' => 'array',
            'transport_back' => 'array',
            'addons' => 'array',
            'recommended_items' => 'array',
            'pricing_breakdown' => 'array',
            'subtotal' => 'float',
            'discount_total' => 'float',
            'grand_total' => 'float',
            'departure_date' => 'date',
            'return_date' => 'date',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function originCounty()
    {
        return $this->belongsTo(County::class, 'origin_county_id');
    }

    public function destination()
    {
        return $this->morphTo('destination', 'destination_type', 'destination_id');
    }

    public function cartItems()
    {
        return $this->morphMany(\App\Models\Marketplace\CartItem::class, 'itemable');
    }

    public function orderItems()
    {
        return $this->morphMany(OrderItem::class, 'itemable');
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function displaySummary(): array
    {
        $out = $this->transport_out[0] ?? null;
        $back = $this->transport_back[0] ?? null;
        return [
            'destination_name' => $this->destination?->name ?? '—',
            'origin' => $this->origin_location ?? $this->originCounty?->name ?? '—',
            'dates' => $this->departure_date?->format('M d') . ' – ' . $this->return_date?->format('M d, Y') ?? '—',
            'guests' => $this->guest_count,
            'transport_out' => $out['name'] ?? ($out['type_label'] ?? $this->transport_mode),
            'transport_back' => $back['name'] ?? ($back['type_label'] ?? 'Same as out'),
            'total' => number_format($this->grand_total),
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($booking) {
            $booking->booking_reference ??= 'EXP-' . strtoupper(\Illuminate\Support\Str::random(10));
        });
    }
}