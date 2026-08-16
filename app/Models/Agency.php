<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Agency extends Model
{
    protected $fillable = [
        'ministry_id', 'name', 'slug', 'code', 'logo',
        'description', 'website', 'contact_email', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function ministry(): BelongsTo
    {
        return $this->belongsTo(Ministry::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
