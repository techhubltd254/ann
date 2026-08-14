<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = ['conversation_id', 'user_id', 'body', 'attachments', 'is_read'];

    protected function casts(): array
    {
        return ['attachments' => 'json', 'is_read' => 'boolean'];
    }

    public function conversation() { return $this->belongsTo(Conversation::class); }
    public function user() { return $this->belongsTo(User::class); }

    public function sender()
    {
        return $this->user_id === auth()->id() ? 'me' : 'them';
    }
}