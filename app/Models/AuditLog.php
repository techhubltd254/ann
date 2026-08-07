<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = ['user_id', 'action', 'resource_type', 'resource_id', 'old_values', 'new_values', 'ip_address', 'user_agent'];
    protected function casts(): array { return ['old_values' => 'json', 'new_values' => 'json']; }
    public function user() { return $this->belongsTo(User::class); }
}
