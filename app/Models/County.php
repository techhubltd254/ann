<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Sector;

class County extends Model
{
    protected $fillable = [
        'name', 'capital', 'code', 'former_province', 'economic_zone',
        'region', 'population_2024', 'area_km2', 'latitude', 'longitude',
        'weather_station_id',         'primary_sectors', 'icon_emoji', 'profile_image',
        'tagline', 'description', 'tourism_highlights',
        'warmest_month', 'coolest_month', 'rainy_season', 'dry_season',
        'slug', 'weather_tags', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'primary_sectors' => 'array',
            'tourism_highlights' => 'array',
            'weather_tags' => 'array',
            'is_active' => 'boolean',
            'latitude' => 'decimal:6',
            'longitude' => 'decimal:6',
        ];
    }

    public function sectors()
    {
        return $this->belongsToMany(Sector::class)
            ->withPivot('sub_sectors');
    }

    public function seasonalCalendars()
    {
        return $this->hasMany(SeasonalCalendar::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function booted(): void
    {
        static::creating(function (County $county) {
            if (empty($county->slug)) {
                $county->slug = Str::slug($county->name);
            }
        });
    }

    public function exhibitions()
    {
        return $this->hasMany(Exhibition::class);
    }

    public function tourismAttractions()
    {
        return $this->hasMany(CountyTourismAttraction::class);
    }

    public function hotels()
    {
        return $this->hasMany(CountyHotel::class);
    }

    public function products()
    {
        return $this->hasMany(CountyProduct::class);
    }

    public function institutions()
    {
        return $this->hasMany(CountyInstitution::class);
    }

    public function farms()
    {
        return $this->hasMany(CountyFarm::class);
    }

    public function transport()
    {
        return $this->hasMany(CountyTransport::class);
    }

    public function healthFacilities()
    {
        return $this->hasMany(CountyHealthFacility::class);
    }

    public function cultureSites()
    {
        return $this->hasMany(CountyCultureSite::class);
    }

    public function profileImageUrl(): string
    {
        if ($this->profile_image && Storage::disk('public')->exists('counties/' . $this->profile_image)) {
            return Storage::disk('public')->url('counties/' . $this->profile_image);
        }
        return '';
    }

    public function getCurrentWeatherTag(): ?string
    {
        $month = now()->month;
        $cal = $this->seasonalCalendars()->where('month', $month)->first();
        return $cal?->weather_tag;
    }

    public function isTourismDestination(): bool
    {
        return in_array('Tourism', $this->primary_sectors ?? []);
    }
}
