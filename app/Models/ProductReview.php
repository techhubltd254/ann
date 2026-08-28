<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductReview extends Model
{
    protected $fillable = [
        'product_id', 'user_id', 'variant_id', 'order_id', 'rating',
        'title', 'body', 'pros', 'cons', 'is_verified_purchase', 'is_approved',
        'helpful_count',
    ];

    protected function casts(): array
    {
        return ['rating' => 'integer', 'is_verified_purchase' => 'boolean', 'is_approved' => 'boolean'];
    }

    public function product() { return $this->belongsTo(\App\Models\Marketplace\Product::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function scopeApproved($q) { return $q->where('is_approved', true); }
    public function scopePending($q) { return $q->where('is_approved', false); }
}