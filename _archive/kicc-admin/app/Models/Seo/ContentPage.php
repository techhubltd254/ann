<?php namespace App\Models\Seo; use Illuminate\Database\Eloquent\Model;
class ContentPage extends Model { protected $table='content_pages'; protected $guarded=[]; protected $casts=['is_published'=>'boolean']; }
