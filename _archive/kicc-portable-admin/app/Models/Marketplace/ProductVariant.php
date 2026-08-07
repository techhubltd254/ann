<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $guarded = [];

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
