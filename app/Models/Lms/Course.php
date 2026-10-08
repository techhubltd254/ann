<?php
namespace App\Models\Lms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use SoftDeletes;
    protected $table = 'courses';
    protected $guarded = [];
    protected $casts = ['is_published' => 'boolean', 'price' => 'decimal:2'];
}
