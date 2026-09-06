<?php namespace App\Models\Seo; use Illuminate\Database\Eloquent\Model;
class ContentPage extends Model { protected $table='content_pages'; protected $fillable=['title','slug','content','excerpt','template','cover_image','author_id','status','published_at','is_featured','is_published']; protected $casts=['is_published'=>'boolean']; }
