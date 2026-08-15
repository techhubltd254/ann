<?php namespace App\Models\Ecommerce;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
class Rfq extends Model {
    protected $table = 'rfqs';
    protected $fillable = ['rfq_number','buyer_id','product_name','quantity','specifications','budget_min','budget_max','deadline','status'];
    public function buyer() { return $this->belongsTo(User::class, 'buyer_id'); }
    public function quotes() { return $this->hasMany(RfqQuote::class); }
}
class RfqQuote extends Model {
    protected $table = 'rfq_quotes';
    protected $fillable = ['rfq_id','seller_id','price','notes','status'];
    public function rfq() { return $this->belongsTo(Rfq::class); }
    public function seller() { return $this->belongsTo(User::class, 'seller_id'); }
}