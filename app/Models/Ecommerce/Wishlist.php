<?php namespace App\Models\Ecommerce;
use Illuminate\Database\Eloquent\Model;
class Wishlist extends Model {
    protected $fillable = ['user_id','wishlistable_type','wishlistable_id'];
    public function user() { return $this->belongsTo(\App\Models\User::class); }
    public function wishlistable() { return $this->morphTo(); }
    public function scopeByUser($q, $userId) { return $q->where('user_id', $userId); }
}