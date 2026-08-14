<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TourGuide extends Model { protected $fillable = ['user_id','county_id','name','bio','languages','certification','service_types','price_per_day','phone','email','photo_url','is_available','is_published'];
    protected function casts(): array { return ['languages'=>'json','service_types'=>'json','price_per_day'=>'float','is_available'=>'boolean','is_published'=>'boolean']; }
    public function county() { return $this->belongsTo(County::class); }
}

class CarRental extends Model { protected $fillable = ['user_id','county_id','company_name','vehicle_type','model','capacity','price_per_day','price_per_km','with_driver','phone','email','location','is_available','is_published'];
    protected function casts(): array { return ['price_per_day'=>'float','price_per_km'=>'float','with_driver'=>'boolean','is_available'=>'boolean','is_published'=>'boolean']; }
    public function county() { return $this->belongsTo(County::class); }
}

class Restaurant extends Model { protected $fillable = ['user_id','county_id','name','description','cuisine_type','price_range','phone','email','website','location','photo_url','opening_hours','has_reservations','is_published'];
    protected function casts(): array { return ['opening_hours'=>'json','has_reservations'=>'boolean','is_published'=>'boolean']; }
    public function county() { return $this->belongsTo(County::class); }
}

class EventOrganizer extends Model { protected $fillable = ['user_id','business_name','contact_email','contact_phone','description','event_types','website','is_verified','is_active'];
    protected function casts(): array { return ['event_types'=>'json','is_verified'=>'boolean','is_active'=>'boolean']; }
}