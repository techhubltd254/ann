<?php namespace App\Models\Logistics; use Illuminate\Database\Eloquent\Model;
class ShippingZone extends Model { protected $table='shipping_zones'; protected $fillable=['name','code','countries','regions','is_active']; protected $casts=['is_active'=>'boolean']; }
