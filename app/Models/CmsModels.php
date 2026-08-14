<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
class Page extends Model {
    protected $fillable = ['title','slug','content','excerpt','featured_image','category','sort_order','is_published'];
    protected function casts(): array { return ['is_published'=>'boolean']; }
    protected static function booted(): void { static::creating(fn($p)=>$p->slug??=Str::slug($p->title)); }
}
class TeamMember extends Model {
    protected $fillable = ['name','title','bio','photo_url','category','sort_order','is_active'];
    protected function casts(): array { return ['is_active'=>'boolean']; }
    public function scopeBoard($q) { return $q->where('category','board'); }
    public function scopeManagement($q) { return $q->where('category','management'); }
}
class TimelineEvent extends Model {
    protected $fillable = ['year','title','description','sort_order'];
}
class FaqItem extends Model {
    protected $fillable = ['question','answer','category','sort_order','is_published'];
    protected function casts(): array { return ['is_published'=>'boolean']; }
}
class ServiceItem extends Model {
    protected $fillable = ['title','description','icon','category','sort_order','is_published'];
    protected function casts(): array { return ['is_published'=>'boolean']; }
}
class VideoItem extends Model {
    protected $fillable = ['title','description','video_url','thumbnail_url','sort_order','is_published'];
    protected function casts(): array { return ['is_published'=>'boolean']; }
}