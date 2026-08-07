<?php

namespace App\Models\Marketplace;

use App\Models\Payment\PaymentIntent;
use App\Models\Payment\SettlementBatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Order extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'subtotal' => 'float', 'discount_total' => 'float', 'tax_total' => 'float',
        'shipping_total' => 'float', 'grand_total' => 'float', 'paid_total' => 'float',
        'is_gift' => 'boolean',
        'placed_at' => 'datetime', 'paid_at' => 'datetime',
        'fulfilled_at' => 'datetime', 'cancelled_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            $order->order_number ??= 'KICC-' . strtoupper(Str::random(8));
            $order->currency ??= 'KES';
            $order->payment_status ??= 'pending';
            $order->fulfillment_status ??= 'unfulfilled';
            $order->placed_at ??= now();
        });
    }

    public function user() { return $this->belongsTo(User::class); }
    public function items() { return $this->hasMany(OrderItem::class); }
    public function paymentIntents() { return $this->morphMany(PaymentIntent::class, 'reference'); }
    public function settlementBatches() { return $this->morphMany(SettlementBatch::class, 'reference'); }
}
