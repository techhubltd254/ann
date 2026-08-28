<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SectorEntityReview extends Model
{
    protected $fillable = [
        'sector_entity_id', 'user_id', 'rating', 'review', 'is_verified_purchase',
    ];

    protected function casts(): array
    {
        return ['rating' => 'integer', 'is_verified_purchase' => 'boolean'];
    }

    public function entity() { return $this->belongsTo(SectorEntity::class, 'sector_entity_id'); }
    public function user() { return $this->belongsTo(User::class); }
}