<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ConsentForm extends Model
{
    protected $fillable = [
        'title', 'slug', 'language', 'content_en', 'content_sw',
        'entity_type', 'entity_id', 'is_active', 'signed_count',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'signed_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn ($m) => $m->slug ??= Str::slug($m->title) . '-' . strtolower(Str::random(4)));
    }

    public function records()
    {
        return $this->hasMany(ConsentRecord::class);
    }

    public function entity()
    {
        return $this->morphTo();
    }
}