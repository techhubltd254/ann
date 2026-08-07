<?php

namespace App\Models\Marketplace;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ShoppingCart extends Model
{
    protected $guarded = [];

    protected $casts = ['expires_at' => 'datetime', 'discount_amount' => 'float'];

    public function user() { return $this->belongsTo(User::class); }
    public function items() { return $this->hasMany(CartItem::class, 'cart_id'); }

    public function getSubtotalAttribute(): float
    {
        return (float) $this->items->sum(fn ($i) => $i->unit_price * $i->quantity);
    }

    public function getItemCountAttribute(): int
    {
        return (int) $this->items->sum('quantity');
    }
}
