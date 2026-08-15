<?php namespace App\Models\Ecommerce;
use Illuminate\Database\Eloquent\Model;
use App\Models\Marketplace\Product;
class FlashSale extends Model {
    protected $table = 'flash_sales';
    protected $fillable = ['title','description','discount_percent','starts_at','ends_at','is_active'];
    public function products() { return $this->belongsToMany(Product::class, 'flash_sale_products')->withPivot(['max_qty','sold_qty'])->withTimestamps(); }
}