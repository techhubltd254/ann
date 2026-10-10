<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CountyProduct extends Model
{
    protected $fillable = ['institution_id', 'county_id', 'userId', 'countyId', 'user_id', 'name', 'description', 'category', 'image_url', 'video_url', 'videos', 'price', 'unit', 'booking_type', 'status', 'is_published', 'isPublished'];
    protected $casts = ['videos' => 'array', 'is_published' => 'boolean', 'price' => 'float'];
    public function county() { return $this->belongsTo(County::class); }
    public function user() { return $this->belongsTo(User::class); }

    protected static function booted(): void
    {
        static::creating(function (self $m) {
            // TiDB raw-schema county_products has SOME camelCase columns
            // (countyId, isPublished) but not others (no bookingType/userId).
            // Introspect which camelCase mirrors actually exist and only map those.
            static $camelCols = null;
            if ($camelCols === null) {
                $camelCols = [];
                try {
                    $cols = \Illuminate\Support\Facades\DB::select('SHOW COLUMNS FROM county_products');
                    foreach ($cols as $c) {
                        $camelCols[] = $c->Field;
                    }
                } catch (\Throwable $e) {
                    $camelCols = [];
                }
            }
            $map = [
                'county_id' => 'countyId', 'user_id' => 'userId',
                'image_url' => 'imageUrl', 'video_url' => 'videoUrl',
                'booking_type' => 'bookingType', 'is_published' => 'isPublished',
            ];
            foreach ($map as $snake => $camel) {
                if (in_array($camel, $camelCols, true) && $m->getAttribute($camel) === null && $m->getAttribute($snake) !== null) {
                    $m->setAttribute($camel, $m->getAttribute($snake));
                }
            }

            // Fill NOT NULL columns without defaults that still lack values.
            static $required = null;
            if ($required === null) {
                $required = [];
                try {
                    $cols = \Illuminate\Support\Facades\DB::select('SHOW COLUMNS FROM county_products');
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
                if (str_ends_with($col, '_id') || str_contains($col, 'Id')) continue;
                if (str_contains($col, 'At') || str_contains($col, 'Date')) {
                    $m->setAttribute($col, now());
                } elseif (str_contains($col, 'Is') || str_contains($col, 'Published')) {
                    $m->setAttribute($col, true);
                } elseif (in_array($col, ['price', 'entry_fee'], true)) {
                    $m->setAttribute($col, 0);
                } elseif (in_array($col, ['status', 'booking_type'], true)) {
                    $m->setAttribute($col, $col === 'status' ? 'available' : 'order');
                } else {
                    $m->setAttribute($col, '');
                }
            }
        });
    }
}