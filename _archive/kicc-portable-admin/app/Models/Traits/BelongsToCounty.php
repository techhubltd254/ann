<?php

namespace App\Models\Traits;

use App\Models\Scopes\TenantScope;

trait BelongsToCounty
{
    protected static function bootBelongsToCounty(): void
    {
        static::addGlobalScope(new TenantScope('county_id'));
    }

    public function county()
    {
        return $this->belongsTo(\App\Models\County::class);
    }
}
