<?php namespace App\Models\Ecommerce;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
class AuctionBid extends Model {
    protected $table = 'auction_bids';
    protected $fillable = ['auction_id','user_id','amount','is_auto'];
    public function auction() { return $this->belongsTo(Auction::class); }
    public function user() { return $this->belongsTo(User::class); }
}