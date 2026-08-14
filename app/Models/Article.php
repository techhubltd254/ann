<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
class Article extends Model {
    protected $fillable = ['title','slug','excerpt','content','category','author','featured_image','tags','status','published_at'];
    protected function casts(): array { return ['tags'=>'json','published_at'=>'datetime']; }
    protected static function booted(): void { static::creating(fn($a)=>$a->slug??=Str::slug($a->title)); }
    public function scopePublished($q) { return $q->where('status','published')->whereNotNull('published_at'); }
}
