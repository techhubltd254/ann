<?php namespace App\Models\Advertising; use Illuminate\Database\Eloquent\Model;
class Creative extends Model { protected $table='ad_creatives'; protected $fillable=['ad_group_id','name','type','headline','description','call_to_action','destination_url','image_url','video_url','width','height','alt_text','status','reviewed_by','reviewed_at','rejection_reason'];
    protected function casts(): array { return ['is_active'=>'boolean','width'=>'integer','height'=>'integer','reviewed_at'=>'datetime']; } }
