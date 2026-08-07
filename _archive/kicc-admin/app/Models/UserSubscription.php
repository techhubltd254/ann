<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSubscription extends Model
{
    protected $fillable = ['user_id', 'subscription_plan_id', 'starts_at', 'ends_at', 'trial_ends_at', 'status', 'payment_provider', 'payment_provider_id'];

    protected function casts(): array { return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'trial_ends_at' => 'datetime']; }

    public function user() { return $this->belongsTo(User::class); }
    public function plan() { return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id'); }
}
