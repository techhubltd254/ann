<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Ministry extends Model
{
    protected $fillable = [
        'name', 'slug', 'code', 'logo', 'color', 'description',
        'website', 'contact_email', 'contact_phone', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function agencies(): HasMany
    {
        return $this->hasMany(Agency::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'ministry_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
