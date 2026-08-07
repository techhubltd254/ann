<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Advertisement extends Model
{
    protected $fillable = ['name', 'type', 'placement', 'user_id', 'target_url', 'image_url', 'budget', 'spent', 'starts_at', 'ends_at', 'is_active', 'impressions', 'clicks'];
    protected function casts(): array { return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'is_active' => 'boolean']; }
}
