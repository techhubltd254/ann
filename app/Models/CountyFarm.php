<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CountyFarm extends Model
{
    protected $fillable = ['county_id', 'name', 'type', 'description', 'location', 'contact', 'size_acres', 'main_crops', 'products', 'is_published'];
    
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'size_acres' => 'decimal:2',
        ];
    }
public function county() { return $this->belongsTo(County::class); }

    protected static function booted(): void
    {
        static::creating(function (self $m) {
        });
    }
}
