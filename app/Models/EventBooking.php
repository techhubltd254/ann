<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class EventBooking extends Model {
    protected $fillable = ['event_id', 'user_id', 'name', 'email', 'phone', 'quantity', 'total_price', 'status'];
}
