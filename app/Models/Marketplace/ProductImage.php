<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    protected $guarded = [];

    public function product() { return $this->belongsTo(Product::class); }
}
