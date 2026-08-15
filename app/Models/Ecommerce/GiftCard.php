<?php namespace App\Models\Ecommerce;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Marketplace\Order;
class GiftCard extends Model {
    protected $table = 'gift_cards';
    protected $fillable = ['code','initial_balance','balance','issued_by_user_id','redeemed_by_user_id','expires_at','redeemed_at','is_active','order_id'];
    protected $casts = ['is_active'=>'boolean','expires_at'=>'datetime','redeemed_at'=>'datetime','initial_balance'=>'float','balance'=>'float'];
    public function issuer() { return $this->belongsTo(User::class, 'issued_by_user_id'); }
    public function redeemer() { return $this->belongsTo(User::class, 'redeemed_by_user_id'); }
    public function order() { return $this->belongsTo(Order::class); }
    public function scopeActive($q) { return $q->where('is_active', true)->where(function($q2) { $q2->whereNull('expires_at')->orWhere('expires_at', '>', now()); }); }
}