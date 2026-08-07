<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeasonalCalendar extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'county_id', 'month', 'avg_temp_c', 'rainfall_mm',
        'tourism_season', 'agri_season', 'weather_tag',
    ];

    protected function casts(): array
    {
        return [
            'month' => 'integer',
            'avg_temp_c' => 'float',
            'rainfall_mm' => 'float',
        ];
    }

    public function county()
    {
        return $this->belongsTo(County::class);
    }
}
