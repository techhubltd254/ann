<?php

namespace App\Models\Marketplace;

use App\Models\County;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $with = ['variants', 'images'];

    protected $fillable = [
        'user_id', 'county_id', 'category_id', 'name', 'slug', 'description',
        'short_description', 'sku', 'barcode', 'unit', 'weight_kg',
        'length_cm', 'width_cm', 'height_cm', 'is_digital', 'status',
        'is_featured', 'meta_title', 'meta_description', 'tags', 'warranty_info',
        'video_url', 'videos', 'video_description', 'model_url',
        'moq', 'fob_price', 'incoterm', 'hs_code', 'export_readiness',
        'certifications', 'trade_enquiry_email', 'is_spotlight_product',
        'pipeline_code',
    ];

    protected $casts = [
        'is_digital' => 'boolean',
        'is_featured' => 'boolean',
        'tags' => 'array',
        'videos' => 'array',
        'weight_kg' => 'float',
        'fob_price' => 'decimal:2',
        'export_readiness' => 'boolean',
        'certifications' => 'json',
        'is_spotlight_product' => 'boolean',
        'moq' => 'integer',
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
        if ($img && !str_contains($img, 'products.jpeg') && !str_contains($img, 'localhost') && !str_contains($img, 'svg')) return $img;
        $variantImg = $this->variants->first()?->image_url;
        if ($variantImg && !str_contains($variantImg, 'svg')) return $variantImg;
        try {
            return app(\App\Services\MediaFallbackResolver::class)->resolve($this);
        } catch (\Throwable $e) {
            return \App\Services\ThumbnailService::placeholder($this->name, $this->category?->name);
        }
    }

    public function hasModel(): bool
    {
        return !empty($this->model_url);
    }

    protected static function booted(): void
    {
        static::creating(function (self $m) {
            // TiDB raw-schema products table mirrors snake columns in camelCase,
            // all NOT NULL without defaults. Mirror known ones + introspection.
            $camelMap = [
                'county_id' => 'countyId', 'user_id' => 'userId',
                'category_id' => 'categoryId', 'is_featured' => 'isFeatured',
                'is_digital' => 'isDigital', 'is_spotlight_product' => 'isSpotlightProduct',
                'export_readiness' => 'exportReadiness', 'moq' => 'moq',
            ];
            foreach ($camelMap as $snake => $camel) {
                if ($m->getAttribute($camel) === null && $m->getAttribute($snake) !== null) {
                    $m->setAttribute($camel, $m->getAttribute($snake));
                }
            }
            static $required = null;
            if ($required === null) {
                try {
                    $cols = \Illuminate\Support\Facades\DB::select('SHOW COLUMNS FROM products');
                    $required = [];
                    foreach ($cols as $c) {
                        $isNull = ($c->Null ?? '') === 'YES';
                        $hasDefault = isset($c->Default) && $c->Default !== null;
                        if (!$isNull && !$hasDefault && !in_array($c->Field, ['id', 'created_at', 'updated_at', 'deleted_at'], true)) {
                            $required[] = $c->Field;
                        }
                    }
                } catch (\Throwable $e) {
                    $required = [];
                }
            }
            foreach ($required as $col) {
                if ($m->getAttribute($col) !== null) continue;
                $snakeGuess = \Illuminate\Support\Str::snake($col);
                if ($m->getAttribute($snakeGuess) !== null) {
                    $m->setAttribute($col, $m->getAttribute($snakeGuess));
                    continue;
                }
                if (str_contains($col, 'At') || str_contains($col, 'Date')) {
                    $m->setAttribute($col, now());
                } elseif (str_contains($col, 'Is') || str_contains($col, 'Featured') || str_contains($col, 'Digital')) {
                    $m->setAttribute($col, false);
                } elseif (in_array($col, ['status'], true)) {
                    $m->setAttribute($col, 'active');
                } else {
                    $m->setAttribute($col, '');
                }
            }
        });
    }
}
