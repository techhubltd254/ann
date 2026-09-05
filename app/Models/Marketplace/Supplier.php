<?php

namespace App\Models\Marketplace;

use App\Models\County;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = ['user_id', 'county_id', 'business_name', 'business_registration', 'kra_pin', 'tax_status', 'contact_phone', 'contact_email', 'website', 'address_line1', 'address_line2', 'city', 'postal_code', 'latitude', 'longitude', 'verification_status', 'verified_at', 'commission_rate', 'payment_terms', 'is_active'];

    protected $casts = ['is_active' => 'boolean', 'commission_rate' => 'float', 'verified_at' => 'datetime'];

    public function user() { return $this->belongsTo(User::class); }
    public function county() { return $this->belongsTo(County::class); }
}
