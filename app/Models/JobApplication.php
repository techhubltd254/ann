<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class JobApplication extends Model {
    protected $fillable = ['job_listing_id','job_id','user_id','name','email','phone','resume','cv_path','cover_letter','status'];
    protected $table = 'job_applications';
    public function job() { return $this->belongsTo(JobListing::class); }
}