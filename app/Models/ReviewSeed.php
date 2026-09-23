<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Seeded online review scores (Google / Tripadvisor) used for display
 * prioritization until real user reviews accumulate. Always attributed.
 */
class ReviewSeed extends Model
{
    protected $fillable = [
        'owner_type', 'owner_id', 'source', 'rating', 'review_count', 'external_url',
    ];

    
    protected function casts(): array
    {
        return [
            'rating' => 'decimal:2',
            'review_count' => 'integer',
        ];
    }
public function sourceLabel(): string
    {
        return match ($this->source) {
            'google' => 'Google reviews',
            'tripadvisor' => 'Tripadvisor',
            'admin' => 'Admin',
            default => $this->source ? ucfirst($this->source) . ' reviews' : '',
        };
    }
}