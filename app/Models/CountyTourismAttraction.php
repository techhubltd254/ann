<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CountyTourismAttraction extends Model
{
    protected $fillable = ['county_id', 'countyId', 'name', 'description', 'category', 'image_url', 'imageUrl', 'location', 'entry_fee', 'entryFee', 'opening_hours', 'openingHours', 'contact', 'latitude', 'latitude', 'longitude', 'is_published', 'isPublished'];
    
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'entry_fee' => 'decimal:2',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }
public function county() { return $this->belongsTo(County::class); }

    protected static function booted(): void
    {
        static::creating(function (self $m) {
            // TiDB raw-schema table mirrors snake columns in camelCase, all NOT
            // NULL without defaults. Mirror known ones + introspection safety net.
            $camelMap = [
                'county_id' => 'countyId', 'image_url' => 'imageUrl',
                'entry_fee' => 'entryFee', 'opening_hours' => 'openingHours',
                'is_published' => 'isPublished',
            ];
            foreach ($camelMap as $snake => $camel) {
                if ($m->getAttribute($camel) === null && $m->getAttribute($snake) !== null) {
                    $m->setAttribute($camel, $m->getAttribute($snake));
                }
            }
            static $required = null;
            if ($required === null) {
                try {
                    $cols = \Illuminate\Support\Facades\DB::select('SHOW COLUMNS FROM county_tourism_attractions');
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
                if (str_contains($col, 'At') || str_contains($col, 'Date')) {
                    $m->setAttribute($col, now());
                } elseif (str_contains($col, 'Is') || str_contains($col, 'Published')) {
                    $m->setAttribute($col, true);
                } elseif (in_array($col, ['entryFee', 'entry_fee'], true)) {
                    $m->setAttribute($col, 0);
                } else {
                    $m->setAttribute($col, '');
                }
            }
        });
    }
}
