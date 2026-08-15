<?php namespace App\Models\Ecommerce;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Marketplace\Product;
class Auction extends Model {
    protected $fillable = ['product_id','seller_id','starting_bid','reserve_price','current_bid','increment','starts_at','ends_at','status','winner_id','winning_bid'];
    public function product() { return $this->belongsTo(Product::class); }
    public function seller() { return $this->belongsTo(User::class, 'seller_id'); }
    public function winner() { return $this->belongsTo(User::class, 'winner_id'); }
    public function bids() { return $this->hasMany(AuctionBid::class); }
}