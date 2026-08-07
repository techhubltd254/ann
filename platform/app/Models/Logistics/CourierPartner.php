<?php namespace App\Models\Logistics; use Illuminate\Database\Eloquent\Model;
class CourierPartner extends Model { protected $table='courier_partners'; protected $guarded=[]; protected $casts=['is_active'=>'boolean']; }
