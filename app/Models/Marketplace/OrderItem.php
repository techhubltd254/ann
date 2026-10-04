<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $guarded = ["id","created_at","updated_at"];

    protected $casts = [
        'unit_price' => 'float', 'total' => 'float',
        'discount_total' => 'float', 'tax_total' => 'float',
        'commission_rate' => 'float', 'commission_amount' => 'float',
        'quantity' => 'integer',
    ];

    public function order() { return $this->belongsTo(Order::class); }
    public function product() { return $this->belongsTo(Product::class); }
    public function variant() { return $this->belongsTo(ProductVariant::class, 'variant_id'); }
    public function supplier() { return $this->belongsTo(Supplier::class); }

    #[EventListener('saving')]
    public function syncProductName(): void
    {
        if ($this->product_name !== null) return;

        $product = $this->product()->first();
        if ($product) {
            $this->product_name = $product->name;
        }
    }
}
