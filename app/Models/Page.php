<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
class Page extends Model {
    protected $fillable = ['title','slug','content','excerpt','featured_image','category','sort_order','is_published'];
    protected function casts(): array { return ['is_published'=>'boolean']; }
    protected static function booted(): void { static::creating(fn($p)=>$p->slug??=Str::slug($p->title)); }
}