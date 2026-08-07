<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'price', 'currency',
        'billing_interval', 'features', 'max_booths', 'max_exhibitions',
        'max_media_files', 'has_livestream', 'has_analytics',
        'has_priority_support', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'json',
            'is_active' => 'boolean',
            'has_livestream' => 'boolean',
            'has_analytics' => 'boolean',
            'has_priority_support' => 'boolean',
        ];
    }
}
