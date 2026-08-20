<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CountyTransport extends Model
{
    protected $table = 'county_transport';
    protected $fillable = ['county_id', 'name', 'type', 'description', 'location', 'operator', 'contact', 'is_published'];
    public function county() { return $this->belongsTo(County::class); }

    protected static function booted(): void
    {
        static::creating(function (self $m) {
            $m->countyId ??= $m->county_id;
        });
    }
}
