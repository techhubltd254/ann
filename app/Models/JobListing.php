<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class JobListing extends Model {
    protected $fillable = ['title','description','department','location','type','closing_date','is_active'];
    protected $table = 'job_listings';
    protected function casts(): array { return ['closing_date'=>'date','is_active'=>'boolean']; }
    public function applications() { return $this->hasMany(JobApplication::class); }
}