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

    public function getVideosAttribute($value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $decoded;
            }
            if (is_string($decoded)) {
                $decoded2 = json_decode($decoded, true);
                if (is_array($decoded2)) {
                    return $decoded2;
                }
            }
            return [];
        }
        return $value ?? [];
    }

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
        $img = $this->images->first()?->url;
        if ($img && !str_contains($img, 'products.jpeg') && !str_contains($img, 'localhost')) return $img;
        $variantImg = $this->variants->first()?->image_url;
        if ($variantImg) return $variantImg;
        try {
            return \App\Services\ThumbnailService::placeholder($this->name, $this->category?->name);
        } catch (\Throwable $e) {
            return 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="800" height="600"><rect width="800" height="600" fill="#0B1E57"/><text x="400" y="300" text-anchor="middle" font-family="sans-serif" font-size="40" font-weight="bold" fill="white">' . e($this->name) . '</text></svg>');
        }
    }
}
