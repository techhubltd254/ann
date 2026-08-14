<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    protected $fillable = [
        'user_id', 'county_id', 'business_name', 'registration_number', 'license_number',
        'tax_id', 'contact_email', 'contact_phone', 'website', 'address', 'description',
        'logo_url', 'service_types', 'agent_type', 'status', 'commission_rate',
        'verified_at', 'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'service_types' => 'json',
            'commission_rate' => 'float',
            'verified_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function user() { return $this->belongsTo(User::class); }
    public function county() { return $this->belongsTo(County::class); }
    public function documents() { return $this->hasMany(AgentDocument::class); }
    public function scopePending($q) { return $q->where('status', 'pending'); }
    public function scopeApproved($q) { return $q->where('status', 'approved'); }
}