<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketType extends Model
{
    protected $fillable = [
        'exhibition_id', 'name', 'slug', 'description',
        'price', 'discount_price', 'currency',
        'quantity', 'sold', 'max_per_order',
        'sale_start', 'sale_end', 'benefits',
        'color', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'discount_price' => 'decimal:2',
            'quantity' => 'integer',
            'sold' => 'integer',
            'max_per_order' => 'integer',
            'sale_start' => 'datetime',
            'sale_end' => 'datetime',
            'benefits' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function exhibition()
    {
        return $this->belongsTo(Exhibition::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
