<?php

namespace App\Models\Subscription;

use App\Models\County;
use Illuminate\Database\Eloquent\Model;

class CountyBulkSlotAllocation extends Model
{
    protected $table = 'county_bulk_slot_allocations';
    protected $fillable = ['county_id', 'plan_id', 'total_slots', 'used_slots', 'price_per_slot', 'purchase_date', 'expiry_date'];
    protected $casts = [
        'total_slots' => 'integer', 'used_slots' => 'integer',
        'price_per_slot' => 'float', 'purchase_date' => 'date', 'expiry_date' => 'date',
    ];

    public function county() { return $this->belongsTo(County::class); }
    public function subscribers() { return $this->hasMany(CountySubscriber::class, 'slot_id'); }

    public function availableSlots(): int { return $this->total_slots - $this->used_slots; }
}
