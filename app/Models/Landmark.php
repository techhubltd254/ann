<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Landmark extends Model
{
    protected $fillable = ['name', 'slug', 'county_id', 'latitude', 'longitude',
        'landmark_type', 'description', 'is_active'];

    protected function casts(): array {
        return ['latitude' => 'decimal:7','longitude' => 'decimal:7','is_active' => 'boolean'];
    }

    protected static function booted(): void {
        static::creating(fn($m) => $m->slug ??= Str::slug($m->name).'-'.strtolower(Str::random(4)));
    }

    public function county() { return $this->belongsTo(County::class); }
}