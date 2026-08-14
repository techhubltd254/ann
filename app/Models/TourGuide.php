<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TourGuide extends Model {
    protected $fillable = ['user_id','county_id','name','bio','languages','certification','service_types','price_per_day','phone','email','photo_url','is_available','is_published'];
    protected function casts(): array { return ['languages'=>'json','service_types'=>'json','price_per_day'=>'float','is_available'=>'boolean','is_published'=>'boolean']; }
    public function county() { return $this->belongsTo(County::class); }
}