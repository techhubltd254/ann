<?php
namespace App\Models\Lms;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $table = 'courses';
    protected $guarded = [];
    protected $casts = ['is_published' => 'boolean'];
    public $timestamps = true;
}
