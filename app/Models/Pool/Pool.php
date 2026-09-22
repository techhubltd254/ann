<?php namespace App\Models\Pool;
use Illuminate\Database\Eloquent\Model;
class Pool extends Model {
    protected $fillable = ['name', 'scope', 'scope_id', 'balance', 'holdback_pct', 'equalisation_pct', 'is_active'];
    protected $casts = ['is_active' => 'boolean', 'balance' => 'float', 'holdback_pct' => 'float', 'equalisation_pct' => 'float'];
    public function contributions() { return $this->hasMany(PoolContribution::class); }
    public function distributions() { return $this->hasMany(PoolDistribution::class); }
}
