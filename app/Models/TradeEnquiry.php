<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TradeEnquiry extends Model
{
    protected $fillable = [
        'reference', 'trade_agreement_id', 'trading_bloc_id', 'county_id',
        'product_id', 'product_name', 'product_category', 'company_name',
        'contact_name', 'contact_email', 'contact_phone', 'destination',
        'message', 'hs_code', 'estimated_value', 'status', 'admin_note', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return ['estimated_value' => 'float', 'reviewed_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $e) {
            $e->reference ??= 'KICC-EXP-' . strtoupper(Str::random(10));
            $e->status ??= 'submitted';
        });
    }

    public function agreement() { return $this->belongsTo(TradeAgreement::class, 'trade_agreement_id'); }
    public function bloc()       { return $this->belongsTo(TradingBloc::class, 'trading_bloc_id'); }
    public function county()     { return $this->belongsTo(County::class); }
}