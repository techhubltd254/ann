<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScreenGroup extends Model
{
    protected $fillable = [
        'name', 'slug', 'location', 'county_id', 'is_active', 'description',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function screens()
    {
        return $this->hasMany(Screen::class, 'group_id');
    }
}