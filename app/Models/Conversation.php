<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = ['user_id', 'vendor_id', 'order_id', 'subject', 'status', 'last_message_at'];

    protected function casts(): array
    {
        return ['last_message_at' => 'datetime'];
    }

    public function user() { return $this->belongsTo(User::class); }
    public function vendor() { return $this->belongsTo(User::class, 'vendor_id'); }
    public function order() { return $this->belongsTo(\App\Models\Marketplace\Order::class); }
    public function messages() { return $this->hasMany(Message::class); }
}