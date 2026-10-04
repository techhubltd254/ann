<?php

namespace App\Models;

use App\Services\DataAvailabilityService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SectorEntity extends Model
{
    protected $fillable = ['county_id', 'countyId', 'sector_id', 'entity_type', 'entityType', 'entity_id', 'entityId', 'name', 'description', 'sector_type', 'sectorType', 'capture_status', 'captureStatus', 'sponsor_funder_tag', 'sponsorFunderTag', 'latitude', 'longitude', 'contact_info', 'contactInfo', 'social_links', 'socialLinks', 'is_published', 'isPublished', 'language_primary', 'languagePrimary', 'tags', 'verification_owner', 'verificationOwner', 'verification_date', 'verificationDate'];
    protected function casts(): array { return ['contact_info' => 'json', 'social_links' => 'json', 'tags' => 'json', 'is_published' => 'boolean', 'verification_date' => 'datetime']; }
    public function county() { return $this->belongsTo(County::class); }
    public function sector() { return $this->belongsTo(Sector::class); }
    public function entity() { return $this->morphTo(); }

    protected static function booted(): void
    {
        static::creating(function (self $m) {
            // TiDB raw-schema table uses camelCase columns that are NOT NULL
            // without defaults. Ensure both snake + camel variants are set.
            $map = [
                'county_id' => 'countyId',
                'sector_id' => 'sectorId',
                'entity_id' => 'entityId',
                'entity_type' => 'entityType',
                'sector_type' => 'sectorType',
                'capture_status' => 'captureStatus',
                'sponsor_funder_tag' => 'sponsorFunderTag',
                'is_published' => 'isPublished',
                'language_primary' => 'languagePrimary',
                'verification_owner' => 'verificationOwner',
                'verification_date' => 'verificationDate',
                'contact_info' => 'contactInfo',
                'social_links' => 'socialLinks',
            ];
            foreach ($map as $snake => $camel) {
                $val = $m->getAttribute($snake);
                if ($camel === 'contactInfo' || $camel === 'socialLinks' || $camel === 'tags') {
                    // Raw camel columns are typically string/JSON — encode arrays.
                    if (is_array($val)) {
                        $m->setAttribute($camel, json_encode($val));
                        continue;
                    }
                }
                if ($m->getAttribute($camel) === null) {
                    $m->setAttribute($camel, $val);
                }
                if ($m->getAttribute($snake) === null && $m->getAttribute($camel) !== null) {
                    $m->setAttribute($snake, $m->getAttribute($camel));
                }
            }

            // Generic safety net: fill any remaining NOT NULL column without a
            // default that this model didn't explicitly set. Introspects the live
            // TiDB schema once per process (cached) so new camelCase columns
            // never break the insert again.
            static $required = null;
            if ($required === null) {
                try {
                    $cols = \Illuminate\Support\Facades\DB::select('SHOW COLUMNS FROM sector_entities');
                    $required = [];
                    foreach ($cols as $c) {
                        $isNull = ($c->Null ?? '') === 'YES';
                        $hasDefault = isset($c->Default) && $c->Default !== null;
                        if (!$isNull && !$hasDefault && !in_array($c->Field, ['id', 'created_at', 'updated_at'], true)) {
                            $required[] = $c->Field;
                        }
                    }
                } catch (\Throwable $e) {
                    $required = [];
                }
            }
            foreach ($required as $col) {
                if ($m->getAttribute($col) !== null) continue;
                $snakeGuess = \Illuminate\Support\Str::snake($col);
                if ($m->getAttribute($snakeGuess) !== null) {
                    $m->setAttribute($col, $m->getAttribute($snakeGuess));
                    continue;
                }
                // Type-aware default based on prefix.
                if (str_contains($col, 'At') || str_contains($col, 'Date')) {
                    $m->setAttribute($col, now());
                } elseif (str_contains($col, 'Is') || str_contains($col, 'Enabled') || str_contains($col, 'Published')) {
                    $m->setAttribute($col, true);
                } elseif (in_array($col, ['tags', 'contactInfo', 'socialLinks'], true)) {
                    $m->setAttribute($col, '[]');
                } elseif (in_array($col, ['captureStatus', 'capture_status', 'sectorType', 'sector_type', 'languagePrimary', 'language_primary'], true)) {
                    $m->setAttribute($col, $col === 'languagePrimary' || $col === 'language_primary' ? 'en' : 'none');
                } else {
                    $m->setAttribute($col, '');
                }
            }
        });

        $invalidate = function (self $m) {
            $dav = app(DataAvailabilityService::class);
            if ($m->county_id) {
                $dav->bustCounty($m->county_id);
            }
            if ($m->county_id && $m->sector_id) {
                $dav->bustSector($m->county_id, $m->sector_id);
            }
        };
        static::saved(fn (self $m) => $invalidate($m));
        static::deleted(fn (self $m) => $invalidate($m));
    }
}
