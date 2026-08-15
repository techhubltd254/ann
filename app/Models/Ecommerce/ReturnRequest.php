<?php namespace App\Models\Ecommerce;
use Illuminate\Database\Eloquent\Model;
class ReturnRequest extends Model {
    protected $table = 'return_requests';
    protected $fillable = ['return_number','order_id','user_id','order_item_id','reason','status','admin_notes','approved_at','refunded_at'];
    public function order() { return $this->belongsTo(\App\Models\Marketplace\Order::class); }
    public function user() { return $this->belongsTo(\App\Models\User::class); }
    public function orderItem() { return $this->belongsTo(\App\Models\Marketplace\OrderItem::class); }
}