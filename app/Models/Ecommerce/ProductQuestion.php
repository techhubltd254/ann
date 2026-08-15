<?php namespace App\Models\Ecommerce;
use Illuminate\Database\Eloquent\Model;
class ProductQuestion extends Model {
    protected $table = 'product_questions';
    protected $fillable = ['product_id','user_id','question','answer','answered_at'];
    protected $casts = ['answered_at'=>'datetime'];
    public function product() { return $this->belongsTo(\App\Models\Marketplace\Product::class); }
    public function user() { return $this->belongsTo(\App\Models\User::class); }
}