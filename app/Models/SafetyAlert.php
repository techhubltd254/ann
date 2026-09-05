<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SafetyAlert extends Model
{
    protected $fillable = ['county_id', 'title', 'description', 'is_active', 'expires_at'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }
}