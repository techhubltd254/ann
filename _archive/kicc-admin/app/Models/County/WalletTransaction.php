<?php

namespace App\Models\County;

use App\Models\County;
use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    protected $table = 'county_wallet_transactions';
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['amount' => 'float', 'running_balance' => 'float'];

    public function county() { return $this->belongsTo(County::class); }
    public function reference() { return $this->morphTo(); }
}
