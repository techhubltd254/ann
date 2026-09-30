<?php

namespace App\Models\Travel;

use Illuminate\Database\Eloquent\Model;

/**
 * Airport transfer service. Maps to `airport_transfers` table.
 */
class AirportTransfer extends Model
{
    protected $table = 'airport_transfers';
    protected $fillable = [
        'airport_id', 'provider_name', 'vehicle_type', 'price',
        'description', 'max_passengers', 'is_active',
    ];

    public function airport()
    {
        return $this->belongsTo(Airport::class);
    }
}