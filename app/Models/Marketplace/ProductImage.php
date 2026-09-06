<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    protected $fillable = ['product_id', 'variant_id', 'url', 'thumbnail_url', 'alt_text', 'sort_order', 'is_primary'];

    protected function casts(): array { return ['is_primary'=>'boolean','sort_order'=>'integer']; }

    public function product() { return $this->belongsTo(Product::class); }
}
