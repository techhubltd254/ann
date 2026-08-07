<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EscrowTransaction extends Model
{
    protected $fillable = ['buyer_id', 'seller_id', 'escrow_id', 'amount', 'currency', 'status', 'reference_type', 'reference_id', 'steps', 'current_step', 'buyer_confirmed_at', 'seller_confirmed_at', 'delivery_confirmed_at', 'released_at'];
    protected function casts(): array { return ['steps' => 'json', 'amount' => 'decimal:2', 'buyer_confirmed_at' => 'datetime', 'seller_confirmed_at' => 'datetime', 'delivery_confirmed_at' => 'datetime', 'released_at' => 'datetime']; }
    public function buyer() { return $this->belongsTo(User::class, 'buyer_id'); }
    public function seller() { return $this->belongsTo(User::class, 'seller_id'); }
}
