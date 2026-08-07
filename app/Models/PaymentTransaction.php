<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    protected $fillable = ['user_id', 'transaction_id', 'provider', 'amount', 'currency', 'status', 'reference_type', 'reference_id', 'metadata'];
    protected function casts(): array { return ['metadata' => 'json', 'amount' => 'decimal:2']; }
    public function user() { return $this->belongsTo(User::class); }
}
