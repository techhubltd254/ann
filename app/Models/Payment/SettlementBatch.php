<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Model;

class SettlementBatch extends Model
{
    protected $guarded = [];
    protected $casts = [
        'total_amount' => 'float', 'fee_amount' => 'float', 'net_amount' => 'float',
        'total_transactions' => 'integer', 'settled_at' => 'datetime',
    ];

    public function gateway() { return $this->belongsTo(Gateway::class, 'gateway_id'); }
}
