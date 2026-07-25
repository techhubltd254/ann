<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Model;

class Gateway extends Model
{
    protected $guarded = [];
    protected $table = 'payment_gateways';
    protected $casts = [
        'is_active' => 'boolean', 'config' => 'json',
        'supported_currencies' => 'array', 'supported_methods' => 'array',
        'min_amount' => 'float', 'max_amount' => 'float',
        'fee_percentage' => 'float', 'fee_fixed' => 'float',
    ];

    public function intents() { return $this->hasMany(PaymentIntent::class, 'gateway_id'); }
    public function logs() { return $this->hasMany(TransactionLog::class, 'gateway_id'); }
}
