<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommissionLog extends Model
{
    protected $fillable = [
        'agent_id', 'order_id', 'order_item_id', 'item_total', 'commission_rate',
        'commission_amount', 'commission_type', 'status', 'settled_at',
    ];

    protected function casts(): array
    {
        return [
            'item_total' => 'float', 'commission_rate' => 'float',
            'commission_amount' => 'float', 'settled_at' => 'datetime',
        ];
    }

    public function agent() { return $this->belongsTo(Agent::class); }
    public function order() { return $this->belongsTo(\App\Models\Marketplace\Order::class); }
}