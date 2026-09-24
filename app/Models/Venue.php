<?php

namespace App\Models;

use App\Services\PipelineRouter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Venue extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'description', 'venue_type',
        'address', 'city', 'county', 'latitude', 'longitude',
        'capacity', 'amenities', 'contact_info', 'cover_image', 'is_active',
        'institution_id', 'pipeline_code',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:6',
            'longitude' => 'decimal:6',
            'capacity' => 'integer',
            'amenities' => 'array',
            'contact_info' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function exhibitions()
    {
        return $this->hasMany(Exhibition::class);
    }

    public function institution()
    {
        return $this->belongsTo(CountyInstitution::class);
    }

    /** The pipeline that processes this venue's transactions. */
    public function pipelineCode(): string
    {
        if ($this->pipeline_code) return $this->pipeline_code;
        // Hotels → tourism pipeline (C1), conference halls → real estate (P2), event spaces → trade (A1)
        return match ($this->venue_type) {
            'hotel', 'lodge', 'resort' => 'C1',
            'conference_hall', 'event_space' => 'P2',
            'stadium', 'arena' => 'A1',
            'outdoor', 'park' => 'C5',
            default => 'P2',
        };
    }

    /** Fee rate for this venue type from pipeline config. */
    public function feeRate(): float
    {
        return app(PipelineRouter::class)->feeRate($this->pipelineCode());
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function booted(): void
    {
        static::creating(function (Venue $venue) {
            if (empty($venue->slug)) {
                $venue->slug = Str::slug($venue->name);
            }
            if (empty($venue->pipeline_code)) {
                $venue->pipeline_code = $venue->pipelineCode();
            }
        });
    }
}