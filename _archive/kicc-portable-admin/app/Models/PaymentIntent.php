<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentIntent extends Model
{
    protected $fillable = [
        'intent_id', 'user_id', 'gateway_id', 'amount', 'currency',
        'status', 'reference_type', 'reference_id', 'description',
        'metadata', 'confirmed_at', 'failed_at', 'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'confirmed_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }
}
