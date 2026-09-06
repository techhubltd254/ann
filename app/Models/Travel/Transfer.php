<?php

namespace App\Models\Travel;

use Illuminate\Database\Eloquent\Model;

class Transfer extends Model { protected $fillable = ['airport_id', 'provider_name', 'vehicle_type', 'capacity', 'price', 'currency', 'description', 'is_active']; protected $table = 'airport_transfers'; }
