<?php namespace App\Models\Ecommerce;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
class Rfq extends Model {
    protected $table = 'rfqs';
    protected $fillable = ['rfq_number','buyer_id','product_name','quantity','specifications','budget_min','budget_max','deadline','status'];
    public function buyer() { return $this->belongsTo(User::class, 'buyer_id'); }
    public function quotes() { return $this->hasMany(RfqQuote::class); }
}