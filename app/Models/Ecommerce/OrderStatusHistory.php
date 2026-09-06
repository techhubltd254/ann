<?php namespace App\Models\Ecommerce;
use Illuminate\Database\Eloquent\Model;
class OrderStatusHistory extends Model {
    protected $table = 'order_status_history';
    protected $fillable = ['order_id','status_from','status_to','notes','changed_by_user_id'];
    protected function casts(): array { return []; }
    public function order() { return $this->belongsTo(\App\Models\Marketplace\Order::class); }
}