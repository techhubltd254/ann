<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TradingBloc extends Model
{
    protected $fillable = ['name', 'code', 'slug', 'description', 'member_states', 'website', 'logo_url', 'status', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $b) => $b->slug ??= Str::slug($b->name));
    }

    public function agreements() { return $this->hasMany(TradeAgreement::class); }
    public function counties() { return $this->belongsToMany(County::class, 'trade_bloc_county')->withPivot('status'); }
}