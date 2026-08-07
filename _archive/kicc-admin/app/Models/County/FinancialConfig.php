<?php

namespace App\Models\County;

use App\Models\County;
use Illuminate\Database\Eloquent\Model;

class FinancialConfig extends Model
{
    protected $table = 'county_financial_config';
    protected $guarded = [];
    protected $casts = [
        'revenue_share_pct' => 'float', 'subscription_discount' => 'float',
        'wallet_balance' => 'float', 'lifetime_earnings' => 'float',
        'total_payouts' => 'float', 'min_payout' => 'float',
        'settlement_day' => 'integer',
    ];

    public function county() { return $this->belongsTo(County::class); }
    public function transactions() { return $this->hasMany(\App\Models\County\WalletTransaction::class, 'county_id'); }
}
