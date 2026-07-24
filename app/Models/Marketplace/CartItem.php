<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $guarded = [];

    protected $casts = ['unit_price' => 'float', 'quantity' => 'integer'];

    public function cart() { return $this->belongsTo(ShoppingCart::class, 'cart_id'); }
    public function variant() { return $this->belongsTo(ProductVariant::class, 'variant_id'); }
}
