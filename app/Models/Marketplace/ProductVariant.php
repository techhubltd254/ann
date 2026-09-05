<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = ['product_id', 'name', 'sku', 'price', 'compare_at_price', 'cost_price', 'stock', 'low_stock_threshold', 'weight_kg', 'is_active', 'sort_order', 'attributes', 'image_url'];

    protected $casts = [
        'price' => 'float',
        'compare_at_price' => 'float',
        'cost_price' => 'float',
        'stock' => 'integer',
        'is_active' => 'boolean',
        'attributes' => 'array',
    ];

    public function product() { return $this->belongsTo(Product::class); }
}
