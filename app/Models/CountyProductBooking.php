<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CountyProductBooking extends Model
{
    protected $fillable = [
        'county_product_id', 'user_id', 'reference', 'customer_name',
        'customer_email', 'customer_phone', 'quantity', 'unit_price', 'total', 'status', 'notes'
    ];

    protected $casts = [
        'unit_price' => 'float',
        'total' => 'float',
        'quantity' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $booking) {
            $booking->reference ??= 'KICC-MUR-' . strtoupper(Str::random(8));
            $booking->status ??= 'pending';
            $booking->total ??= $booking->unit_price * $booking->quantity;
        });
    }

    public function product() { return $this->belongsTo(CountyProduct::class, 'county_product_id'); }
    public function user() { return $this->belongsTo(User::class); }
}
