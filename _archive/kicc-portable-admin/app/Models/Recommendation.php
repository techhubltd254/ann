<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recommendation extends Model
{
    protected $fillable = [
        'recommendable_type', 'recommendable_id', 'user_id',
        'score', 'reason', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'float',
            'expires_at' => 'datetime',
        ];
    }

    public function recommendable()
    {
        return $this->morphTo();
    }
}
