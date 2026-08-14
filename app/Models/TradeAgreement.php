<?php

namespace App\Models;

use App\Models\Marketplace\ProductCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TradeAgreement extends Model
{
    protected $fillable = [
        'trading_bloc_id', 'title', 'slug', 'summary', 'content',
        'partner_country', 'agreement_type', 'signed_date', 'effective_date',
        'status', 'document_url', 'document_pdf', 'is_featured', 'is_active',
        'benefits', 'sector_coverage', 'county_impact',
    ];

    protected function casts(): array
    {
        return [
            'signed_date' => 'date',
            'effective_date' => 'date',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'benefits' => 'json',
            'sector_coverage' => 'json',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $a) => $a->slug ??= Str::slug($a->title));
    }

    public function bloc() { return $this->belongsTo(TradingBloc::class, 'trading_bloc_id'); }
    public function categories() { return $this->belongsToMany(ProductCategory::class, 'trade_agreement_product_category'); }
    public function scopeActive($q) { return $q->where('is_active', true); }
    public function scopeFeatured($q) { return $q->where('is_featured', true); }
}