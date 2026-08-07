<?php namespace App\Models\Logistics; use Illuminate\Database\Eloquent\Model;
class ShippingRate extends Model { protected $table='shipping_rates'; protected $guarded=[]; protected $casts=['rate'=>'float','min_weight'=>'float','max_weight'=>'float']; }
