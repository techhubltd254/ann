<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CountyProduct extends Model
{
    protected $fillable = ['county_id', 'user_id', 'name', 'description', 'category', 'price', 'unit', 'status', 'is_published'];
    public function county() { return $this->belongsTo(County::class); }
    public function user() { return $this->belongsTo(User::class); }
}
