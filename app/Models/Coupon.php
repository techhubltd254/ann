<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'code', 'description', 'discount_type', 'discount_value', 'min_order_amount',
        'max_discount', 'usage_limit', 'usage_count', 'per_user_limit',
        'starts_at', 'expires_at', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'float', 'min_order_amount' => 'float', 'max_discount' => 'float',
            'starts_at' => 'datetime', 'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function usage() { return $this->hasMany(CouponUsage::class); }

    public function isValid(): bool
    {
        if (!$this->is_active) return false;
        if ($this->expires_at && $this->expires_at->isPast()) return false;
        if ($this->starts_at && $this->starts_at->isFuture()) return false;
        if ($this->usage_limit && $this->usage_count >= $this->usage_limit) return false;
        return true;
    }

    public function calculateDiscount(float $subtotal): float
    {
        $discount = $this->discount_type === 'percentage'
            ? $subtotal * ($this->discount_value / 100)
            : $this->discount_value;
        if ($this->max_discount) $discount = min($discount, $this->max_discount);
        return round(max(0, $discount), 2);
    }
}