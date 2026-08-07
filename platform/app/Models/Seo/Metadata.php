<?php namespace App\Models\Seo; use Illuminate\Database\Eloquent\Model;
class Metadata extends Model { protected $table='seo_metadata'; protected $guarded=[]; public function pageable(){return $this->morphTo();} }
