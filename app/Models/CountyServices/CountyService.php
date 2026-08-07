<?php namespace App\Models\CountyServices; use App\Models\County; use Illuminate\Database\Eloquent\Model;
class CountyService extends Model { protected $table='sector_entities'; protected $guarded=[]; protected $casts=['is_active'=>'boolean']; public function county(){return $this->belongsTo(County::class);} }
