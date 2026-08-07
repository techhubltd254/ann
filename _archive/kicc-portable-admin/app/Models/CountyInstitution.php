<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToCounty;

class CountyInstitution extends Model {

    use BelongsToCounty;
    protected $fillable = ['county_id', 'name', 'type', 'description', 'location', 'phone', 'email', 'website', 'student_count', 'is_published'];
    public function county() { return $this->belongsTo(County::class); }
}
