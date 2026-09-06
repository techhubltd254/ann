<?php namespace App\Models\Logistics; use Illuminate\Database\Eloquent\Model;
class CourierPartner extends Model { protected $table='courier_partners'; protected $fillable=['name','slug','api_endpoint','api_key_encrypted','tracking_url_template','supported_countries','services','is_active']; protected $casts=['is_active'=>'boolean']; }
