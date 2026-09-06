<?php namespace App\Models\Ecommerce;
use Illuminate\Database\Eloquent\Model;
class FlashSaleProduct extends Model {
    protected $table = 'flash_sale_products';
    protected $fillable = ['flash_sale_id','product_id','max_qty','sold_qty'];
    protected function casts(): array { return ['max_qty'=>'integer','sold_qty'=>'integer']; }
}