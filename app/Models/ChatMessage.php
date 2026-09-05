<?php

namespace App\Models;

use App\Models\LiveStream;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'live_stream_id' => 'integer',
        ];
    }

    public function liveStream()
    {
        return $this->belongsTo(LiveStream::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}