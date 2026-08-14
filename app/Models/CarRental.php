<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CarRental extends Model {
    protected $fillable = ['user_id','county_id','company_name','vehicle_type','model','capacity','price_per_day','price_per_km','with_driver','phone','email','location','is_available','is_published'];
    protected function casts(): array { return ['price_per_day'=>'float','price_per_km'=>'float','with_driver'=>'boolean','is_available'=>'boolean','is_published'=>'boolean']; }
    public function county() { return $this->belongsTo(County::class); }
}