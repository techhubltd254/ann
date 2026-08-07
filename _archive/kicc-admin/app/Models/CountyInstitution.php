<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CountyInstitution extends Model
{
    protected $fillable = ['county_id', 'name', 'type', 'description', 'location', 'phone', 'email', 'website', 'student_count', 'is_published'];
    public function county() { return $this->belongsTo(County::class); }
}
