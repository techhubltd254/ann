<?php namespace App\Models\Pool;
use Illuminate\Database\Eloquent\Model;
class PoolContribution extends Model {
    protected $fillable = ['pool_id','source_type','source_id','county_id','sector_id','entity_type','entity_id','gross_amount','platform_fee','pool_share','quality_score','period_id'];
    protected $casts = ['gross_amount'=>'float','platform_fee'=>'float','pool_share'=>'float','quality_score'=>'float'];
    public function pool() { return $this->belongsTo(Pool::class); }
}
