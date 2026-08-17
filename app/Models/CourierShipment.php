<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourierShipment extends Model
{
    protected $fillable = ['escrow_transaction_id', 'tracking_number', 'courier_name', 'status', 'origin_address', 'destination_address', 'shipped_at', 'estimated_delivery', 'delivered_at'];

    public function escrowTransaction() { return $this->belongsTo(EscrowTransaction::class); }
    public function trackingEvents() { return $this->hasMany(CourierTrackingEvent::class); }
}