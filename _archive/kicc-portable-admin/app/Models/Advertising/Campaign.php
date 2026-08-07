<?php namespace App\Models\Advertising; use Illuminate\Database\Eloquent\Model;
class Campaign extends Model { protected $table='ad_campaigns'; protected $guarded=[]; protected $casts=['is_active'=>'boolean','budget'=>'float','start_date'=>'datetime','end_date'=>'datetime']; }
