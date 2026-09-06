<?php namespace App\Models\Ecommerce;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
class RfqQuote extends Model {
    protected $table = 'rfq_quotes';
    protected $fillable = ['rfq_id','seller_id','price','notes','status'];
    protected function casts(): array { return ['price'=>'float']; }
    public function rfq() { return $this->belongsTo(Rfq::class); }
    public function seller() { return $this->belongsTo(User::class, 'seller_id'); }
}