<?php

namespace App\Models\Travel;

use App\Models\County;
use Illuminate\Database\Eloquent\Model;

class Hotel extends Model { protected $guarded = []; public function county() { return $this->belongsTo(County::class); } }
