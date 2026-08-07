<?php

namespace App\Models\Subscription;

use App\Models\County;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CountySubscriber extends Model
{
    protected $table = 'county_subscribers';
    protected $guarded = [];
    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime'];

    public function county() { return $this->belongsTo(County::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function plan() { return $this->belongsTo(CountySubscriptionPlan::class, 'plan_id'); }
    public function slot() { return $this->belongsTo(CountyBulkSlotAllocation::class, 'slot_id'); }
}
