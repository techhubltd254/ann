<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];

    public function parent() { return $this->belongsTo(self::class, 'parent_id'); }
    public function children() { return $this->hasMany(self::class, 'parent_id'); }
    public function products() { return $this->hasMany(Product::class, 'category_id'); }

    public function scopeActive($q) { return $q->where('is_active', true)->orderBy('sort_order'); }
}
