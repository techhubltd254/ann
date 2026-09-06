<?php namespace App\Models\Seo; use Illuminate\Database\Eloquent\Model;
class Metadata extends Model { protected $table='seo_metadata'; protected $fillable=['pageable_type','pageable_id','meta_title','meta_description','keywords','og_title','og_description','og_image','og_type','canonical_url','no_index','no_follow','structured_data']; public function pageable(){return $this->morphTo();} }
