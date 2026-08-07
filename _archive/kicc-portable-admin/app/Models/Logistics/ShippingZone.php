<?php namespace App\Models\Logistics; use Illuminate\Database\Eloquent\Model;
class ShippingZone extends Model { protected $table='shipping_zones'; protected $guarded=[]; protected $casts=['is_active'=>'boolean']; }
