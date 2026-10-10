<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class InstitutionExperience extends Model {
 protected $guarded=['id'];
 protected $casts=['inclusions'=>'array','requirements'=>'array'];
 public function product(){return $this->belongsTo(\App\Models\Marketplace\Product::class);}
 public function institution(){return $this->belongsTo(CountyInstitution::class);}
}
