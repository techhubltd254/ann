<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Restaurant extends Model {
    protected $fillable = ['user_id','county_id','name','description','cuisine_type','price_range','phone','email','website','location','photo_url','opening_hours','has_reservations','is_published'];
    protected function casts(): array { return ['opening_hours'=>'json','has_reservations'=>'boolean','is_published'=>'boolean']; }
    public function county() { return $this->belongsTo(County::class); }
}