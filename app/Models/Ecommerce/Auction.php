<?php namespace App\Models\Ecommerce;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Marketplace\Product;
class Auction extends Model {
    protected $fillable = ['product_id','seller_id','starting_bid','reserve_price','current_bid','increment','starts_at','ends_at','status','winner_id','winning_bid'];
    protected $casts = ['starting_bid'=>'float','current_bid'=>'float','reserve_price'=>'float','winning_bid'=>'float','increment'=>'float','starts_at'=>'datetime','ends_at'=>'datetime','is_auto'=>'boolean'];
    public function product() { return $this->belongsTo(Product::class); }
    public function seller() { return $this->belongsTo(User::class, 'seller_id'); }
    public function winner() { return $this->belongsTo(User::class, 'winner_id'); }
    public function bids() { return $this->hasMany(AuctionBid::class); }
    public function scopeActive($q) { return $q->where('status', 'active')->where('ends_at', '>', now()); }
}