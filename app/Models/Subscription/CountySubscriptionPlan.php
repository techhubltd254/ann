<?php

namespace App\Models\Subscription;

use App\Models\County;
use Illuminate\Database\Eloquent\Model;

class CountySubscriptionPlan extends Model
{
    protected $table = 'county_subscription_plans';
    protected $fillable = ['county_id', 'name', 'slug', 'description', 'price', 'max_booths', 'max_products', 'has_analytics', 'has_livestream', 'has_priority_support', 'is_active', 'features', 'sort_order'];
    protected $casts = [
        'price' => 'float', 'max_booths' => 'integer', 'max_products' => 'integer',
        'has_analytics' => 'boolean', 'has_livestream' => 'boolean', 'has_priority_support' => 'boolean',
        'is_active' => 'boolean', 'features' => 'array',
    ];

    public function scopeActive($q) { return $q->where('is_active', true); }
    public function county() { return $this->belongsTo(County::class); }
    public function subscribers() { return $this->hasMany(CountySubscriber::class, 'plan_id'); }
}
