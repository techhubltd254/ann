<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CouponUsage extends Model
{
    protected $fillable = ['coupon_id', 'user_id', 'order_id', 'discount_amount'];
    protected $table = 'coupon_usage';
    
    protected function casts(): array
    {
        return [
            'discount_amount' => 'decimal:2',
        ];
    }
public function coupon() { return $this->belongsTo(Coupon::class); }
    public function order() { return $this->belongsTo(\App\Models\Marketplace\Order::class); }
}