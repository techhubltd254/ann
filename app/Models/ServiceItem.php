<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ServiceItem extends Model {
    protected $fillable = ['title','description','icon','category','sort_order','is_published'];
    protected function casts(): array { return ['is_published'=>'boolean']; }
}