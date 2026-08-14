<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserNotification extends Model
{
    protected $fillable = ['user_id', 'type', 'title', 'body', 'action_url', 'is_read', 'read_at'];
    protected $table = 'user_notifications';
    protected function casts(): array { return ['is_read' => 'boolean', 'read_at' => 'datetime']; }
    public function user() { return $this->belongsTo(User::class); }
    public function scopeUnread($q) { return $q->where('is_read', false); }
}