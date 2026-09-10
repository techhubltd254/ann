<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FloorPlan extends Model
{
    protected $fillable = [
        'exhibition_id', 'venue_id', 'name',
        'image_url', 'layout_data', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'layout_data' => 'array',
            'is_published' => 'boolean',
        ];
    }

    public function exhibition()
    {
        return $this->belongsTo(Exhibition::class);
    }

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }
}