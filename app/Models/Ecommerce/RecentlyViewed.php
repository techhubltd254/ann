<?php namespace App\Models\Ecommerce;
use Illuminate\Database\Eloquent\Model;
class RecentlyViewed extends Model { { protected $fillable = ['*']; }
    protected $table = 'recently_viewed';
    public $timestamps = false;
    protected $fillable = ['user_id','session_id','viewable_type','viewable_id','viewed_at'];
    protected function casts(): array { return ['viewed_at'=>'datetime']; }
    public function viewable() { return $this->morphTo(); }
}