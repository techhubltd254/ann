<?php

namespace App\Models\Subscription;

use App\Models\County;
use Illuminate\Database\Eloquent\Model;

class CountySubscriptionPlan extends Model
{
    protected $table = 'county_subscription_plans';
    protected $guarded = [];
    protected $casts = [
        'price' => 'float', 'max_booths' => 'integer', 'max_products' => 'integer',
        'has_analytics' => 'boolean', 'has_livestream' => 'boolean', 'has_priority_support' => 'boolean',
        'is_active' => 'boolean', 'features' => 'array',
    ];

    public function scopeActive($q) { return $q->where('is_active', true); }
    public function county() { return $this->belongsTo(County::class); }
    public function subscribers() { return $this->hasMany(CountySubscriber::class, 'plan_id'); }
}
