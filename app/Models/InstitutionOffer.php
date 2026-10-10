<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class InstitutionOffer extends Model {
 protected $guarded=['id'];
 protected $casts=['is_published'=>'boolean','starts_at'=>'datetime','ends_at'=>'datetime','price'=>'decimal:2'];
 public function product(){return $this->belongsTo(\App\Models\Marketplace\Product::class);}
 public function institution(){return $this->belongsTo(CountyInstitution::class);}
 public function scopeCurrent($q){return $q->where('is_published',true)->where(fn($q)=>$q->whereNull('starts_at')->orWhere('starts_at','<=',now()))->where(fn($q)=>$q->whereNull('ends_at')->orWhere('ends_at','>=',now()));}
}
