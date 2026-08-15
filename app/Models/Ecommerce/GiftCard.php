<?php namespace App\Models\Ecommerce;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
class GiftCard extends Model {
    protected $table = 'gift_cards';
    protected $fillable = ['code','initial_balance','balance','issued_by_user_id','redeemed_by_user_id','expires_at','redeemed_at','is_active'];
    public function issuer() { return $this->belongsTo(User::class, 'issued_by_user_id'); }
    public function redeemer() { return $this->belongsTo(User::class, 'redeemed_by_user_id'); }
    public function scopeActive($q) { return $q->where('is_active', true)->where(function($q2) { $q2->whereNull('expires_at')->orWhere('expires_at', '>', now()); }); }
}