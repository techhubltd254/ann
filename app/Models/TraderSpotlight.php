<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TraderSpotlight extends Model
{
    protected $fillable = [
        'name', 'slug', 'trader_type', 'institution_id', 'product_id', 'county_id',
        'contact_name', 'contact_mobile', 'contact_whatsapp', 'contact_email',
        'department_lead', 'description', 'spotlight_video_id',
        'duration_seconds', 'is_verified', 'is_published', 'trade_info',
    ];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'is_published' => 'boolean',
            'duration_seconds' => 'integer',
            'trade_info' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn ($m) => $m->slug ??= Str::slug($m->name) . '-' . strtolower(Str::random(4)));
    }

    public function institution()
    {
        return $this->belongsTo(CountyInstitution::class);
    }

    public function product()
    {
        return $this->belongsTo(Marketplace\Product::class);
    }

    public function county()
    {
        return $this->belongsTo(County::class);
    }

    public function spotlightVideo()
    {
        return $this->belongsTo(MediaAsset::class, 'spotlight_video_id');
    }
}