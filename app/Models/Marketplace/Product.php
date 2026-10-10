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
        'user_id', 'county_id', 'institution_id', 'category_id', 'name', 'slug', 'description',
        'short_description', 'sku', 'barcode', 'unit', 'weight_kg',
        'length_cm', 'width_cm', 'height_cm', 'is_digital', 'status',
        'is_featured', 'meta_title', 'meta_description', 'tags', 'warranty_info',
        'video_url', 'videos', 'video_description', 'model_url',
        'moq', 'fob_price', 'incoterm', 'hs_code', 'export_readiness',
        'certifications', 'trade_enquiry_email', 'is_spotlight_product',
        'pipeline_code', 'offering_kind', 'price_mode', 'sync_key', 'source_url', 'source_verified_at', 'booking_url', 'offering_details',
    ];

    protected $casts = [
        'is_digital' => 'boolean',
        'is_featured' => 'boolean',
        'tags' => 'array',
        'offering_details' => 'array', 'source_verified_at' => 'datetime',
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

    public function experiences() { return $this->hasOne(\App\Models\InstitutionExperience::class); }
    public function offers() { return $this->hasMany(\App\Models\InstitutionOffer::class); }

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

    /**
     * A stored URL is only usable if it still resolves. The legacy Worker host
     * (kicc-r2-media.*.workers.dev) is retired and returns 404, and the old
     * `products.jpeg` seed stills were never uploaded — treat both as absent so
     * the resolver can supply a real, entity-bound image instead of a broken
     * <img>.
     */
    public static function usableImageUrl(?string $url): ?string
    {
        if (! $url || $url === '') {
            return null;
        }
        foreach (['workers.dev', 'products.jpeg', 'products.jpg', 'localhost', 'data:', '.svg'] as $dead) {
            if (str_contains($url, $dead)) {
                return null;
            }
        }

        return $url;
    }

    public function getImageUrlAttribute(): string
    {
        $media = app(\App\Services\ProductMediaResolver::class)->resolve($this);
        return $media['url'] ?? \App\Services\ThumbnailService::placeholder($this->name, $this->category?->name);
    }

    public function hasModel(): bool
    {
        return !empty($this->model_url);
    }

    protected static function booted(): void
    {
        static::creating(function (self $m) {
            // The commerce `products` table is pure snake_case (no camelCase
            // mirror like sector_entities/county_institutions). Only fill any
            // actually-required NOT NULL no-default columns via introspection.
            static $required = null;
            if ($required === null) {
                try {
                    $cols = \Illuminate\Support\Facades\DB::select('SHOW COLUMNS FROM products');
                    $required = [];
                    foreach ($cols as $c) {
                        $isNull = ($c->Null ?? '') === 'YES';
                        $hasDefault = isset($c->Default) && $c->Default !== null;
                        if (!$isNull && !$hasDefault && !in_array($c->Field, ['id', 'created_at', 'updated_at', 'deleted_at', 'user_id'], true)) {
                            $required[] = $c->Field;
                        }
                    }
                } catch (\Throwable $e) {
                    $required = [];
                }
            }
            foreach ($required as $col) {
                if ($m->getAttribute($col) !== null) continue;
                // Never force *_id foreign-key columns — a blank FK would corrupt the row.
                if (str_ends_with($col, '_id')) continue;
                if (str_contains($col, 'At') || str_contains($col, 'Date')) {
                    $m->setAttribute($col, now());
                } elseif (in_array($col, ['status'], true)) {
                    $m->setAttribute($col, 'active');
                } elseif (in_array($col, ['moq', 'weight_kg', 'length_cm', 'width_cm', 'height_cm'], true)) {
                    $m->setAttribute($col, 0);
                } else {
                    $m->setAttribute($col, '');
                }
            }
        });
    }
}
