<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class EventOrganizer extends Model {
    protected $fillable = ['user_id','business_name','contact_email','contact_phone','description','event_types','website','is_verified','is_active'];
    protected function casts(): array { return ['event_types'=>'json','is_verified'=>'boolean','is_active'=>'boolean']; }
}