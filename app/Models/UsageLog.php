<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsageLog extends Model
{
    protected $table = 'usage_logs';
    protected $fillable = ['*'];
}
