<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class VideoItem extends Model {
    protected $fillable = ['title','description','video_url','thumbnail_url','sort_order','is_published'];
    protected function casts(): array { return ['is_published'=>'boolean']; }
}