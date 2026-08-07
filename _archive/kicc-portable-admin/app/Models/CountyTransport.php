<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToCounty;

class CountyTransport extends Model {

    use BelongsToCounty;
    protected $table = 'county_transport';
    protected $fillable = ['county_id', 'name', 'type', 'description', 'location', 'operator', 'contact', 'is_published'];
    public function county() { return $this->belongsTo(County::class); }
}
