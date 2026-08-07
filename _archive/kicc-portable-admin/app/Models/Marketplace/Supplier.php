<?php

namespace App\Models\Marketplace;

use App\Models\County;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean', 'commission_rate' => 'float', 'verified_at' => 'datetime'];

    public function user() { return $this->belongsTo(User::class); }
    public function county() { return $this->belongsTo(County::class); }
}
