<?php

namespace App\Models\Marketplace;

use App\Models\County;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_digital' => 'boolean',
        'is_featured' => 'boolean',
        'tags' => 'array',
        'weight_kg' => 'float',
    ];

    public function scopeActive($q) { return $q->where('status', 'active'); }

    public function county() { return $this->belongsTo(County::class); }
    public function category() { return $this->belongsTo(ProductCategory::class, 'category_id'); }
    public function seller() { return $this->belongsTo(User::class, 'user_id'); }
    public function variants() { return $this->hasMany(ProductVariant::class); }
    public function images() { return $this->hasMany(ProductImage::class); }
    public function supplier()
    {
        return $this->hasOneThrough(Supplier::class, User::class, 'id', 'user_id', 'user_id', 'id');
    }

    public function getPriceAttribute(): ?float
    {
        return $this->variants->min('price');
    }

    public function getImageUrlAttribute(): string
    {
        $img = $this->images->first()?->url
            ?? $this->variants->first()?->image_url;
        if ($img) return $img;
        $slug = $this->county?->slug;
        return $slug ? media("counties/{$slug}/products.jpeg") : asset('storage/kicc/logo.png');
    }
}
