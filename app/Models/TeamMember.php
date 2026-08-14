<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TeamMember extends Model {
    protected $fillable = ['name','title','bio','photo_url','category','sort_order','is_active'];
    protected function casts(): array { return ['is_active'=>'boolean']; }
    public function scopeBoard($q) { return $q->where('category','board'); }
    public function scopeManagement($q) { return $q->where('category','management'); }
}