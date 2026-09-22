<?php namespace App\Models\Pool;
use Illuminate\Database\Eloquent\Model;
class PoolDistribution extends Model {
    protected $fillable = ['pool_id','period_id','beneficiary_type','beneficiary_id','contribution_weight','quality_weight','final_weight','amount','breakdown','status','settled_at'];
    protected $casts = ['contribution_weight'=>'float','quality_weight'=>'float','final_weight'=>'float','amount'=>'float','breakdown'=>'array','settled_at'=>'datetime'];
    public function pool() { return $this->belongsTo(Pool::class); }
}
