<?php

namespace App\Models\Marketplace;

use App\Models\ExperienceBooking;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $fillable = ['cart_id', 'variant_id', 'quantity', 'unit_price', 'itemable_type', 'itemable_id'];

    protected $casts = ['unit_price' => 'float', 'quantity' => 'integer'];

    public function cart() { return $this->belongsTo(ShoppingCart::class, 'cart_id'); }
    public function variant() { return $this->belongsTo(ProductVariant::class, 'variant_id'); }

    public function itemable()
    {
        return $this->morphTo();
    }

    public function isExperience(): bool
    {
        return $this->itemable_type === ExperienceBooking::class && $this->itemable_id !== null;
    }

    public function displayName(): string
    {
        if ($this->isExperience()) {
            $booking = $this->itemable;
            if ($booking) return $booking->destination?->name ?? 'Experience Trip';
            return 'Experience Trip';
        }
        return $this->variant?->product?->name ?? 'Product';
    }

    public function displayDescription(): string
    {
        if ($this->isExperience()) {
            $booking = $this->itemable;
            if ($booking) {
                $sum = $booking->displaySummary();
                return $sum['origin'] . ' → ' . $sum['destination_name'] . ' | ' . $sum['dates'];
            }
            return 'Custom experience booking';
        }
        return $this->variant?->product?->short_description ?? '';
    }
}
