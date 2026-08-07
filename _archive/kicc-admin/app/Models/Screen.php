<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Screen extends Model
{
    protected $table = 'screens';

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'label', 'location', 'county_id', 'sector_id',
        'target_duration_sec', 'min_images', 'max_images',
        'refresh_interval_min', 'active',
    ];

    protected function casts(): array
    {
        return [
            'target_duration_sec' => 'integer',
            'min_images' => 'integer',
            'max_images' => 'integer',
            'refresh_interval_min' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function getVideoPathAttribute(): string
    {
        return 'screens/auto_' . $this->id . '.mp4';
    }

    public function getVideoUrlAttribute(): string
    {
        return asset('storage/' . $this->video_path);
    }

    public function getPresetKeyAttribute(): string
    {
        return match (true) {
            str_starts_with($this->id, 'county_main') => 'county_main',
            str_starts_with($this->id, 'county_sub') => 'county_sub',
            str_starts_with($this->id, 'sector_national') => 'sector_pavilion',
            str_starts_with($this->id, 'sector') => 'sector_pavilion',
            $this->id === 'national_govt' => 'hero_wall',
            str_starts_with($this->id, 'agency') => 'info_kiosk',
            str_starts_with($this->id, 'hero') => 'hero_wall',
            str_starts_with($this->id, 'info') => 'info_kiosk',
            str_starts_with($this->id, 'hallway') => 'hallway',
            default => 'county_sub',
        };
    }

    public function getTypeTagAttribute(): string
    {
        return match (true) {
            str_starts_with($this->id, 'county') => 'county',
            str_starts_with($this->id, 'sector_national') => 'national_sector',
            str_starts_with($this->id, 'sector') => 'sector',
            $this->id === 'national_govt' => 'national',
            str_starts_with($this->id, 'agency') => 'agency',
            str_starts_with($this->id, 'hero') => 'hero',
            str_starts_with($this->id, 'hallway') => 'hallway',
            default => 'other',
        };
    }

    public function county()
    {
        return $this->belongsTo(County::class, 'county_id', 'slug');
    }
}
