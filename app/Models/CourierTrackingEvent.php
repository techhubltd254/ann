<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourierTrackingEvent extends Model
{
    protected $fillable = ['courier_shipment_id', 'status', 'location', 'description', 'occurred_at'];

    
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
        ];
    }
public function shipment() { return $this->belongsTo(CourierShipment::class, 'courier_shipment_id'); }
}