<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Generic publishing record, ported from the legacy KICC admin.
 * `type` is one of config('kicc.types'); `payload` keeps the original structured fields.
 */
class Record extends Model
{
    use HasUuids;

    protected $fillable = ['type', 'slug', 'name', 'description', 'status', 'payload', 'parent_id', 'updated_by'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'revision' => 'integer'];
    }

    public function media(): HasMany
    {
        return $this->hasMany(RecordMedia::class)->orderByDesc('created_at');
    }

    public function publicMedia(): HasMany
    {
        return $this->hasMany(RecordMedia::class)->where('status', 'published')->orderByDesc('created_at');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function scopePublished($q)
    {
        return $q->where('status', 'published');
    }

    /**
     * Bridge a native domain model to its publishing record, so the county ->
     * sector -> institution chain can be mirrored without duplicating content.
     */
    public static function findByOwner(string $ownerType, int $ownerId): ?self
    {
        $typeMap = [
            'App\\Models\\County' => 'counties',
            'county' => 'counties',
            'App\\Models\\CountyInstitution' => 'institutions',
            'institution' => 'institutions',
            'App\\Models\\Sector' => 'sectors',
        ];

        $type = $typeMap[$ownerType] ?? null;
        if (!$type) {
            return null;
        }

        return static::where('type', $type)->where('payload->source_id', $ownerId)->first();
    }
}
