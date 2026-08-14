<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'user_id', 'reviewable_type', 'reviewable_id', 'rating', 'content',
        'photos', 'is_verified', 'status', 'vendor_response', 'responded_at',
    ];

    protected function casts(): array
    {
        return ['photos' => 'json', 'rating' => 'integer', 'is_verified' => 'boolean', 'responded_at' => 'datetime'];
    }

    public function reviewable() { return $this->morphTo(); }
    public function user() { return $this->belongsTo(User::class); }
    public function scopeApproved($q) { return $q->where('status', 'approved'); }
    public function scopePending($q) { return $q->where('status', 'pending'); }
}