<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
class EventBooking extends Model {
    protected $fillable = ['reference','first_name','last_name','organization','email','phone','event_name','event_type','expected_attendees','event_date','duration_days','preferred_venue','needs_catering','catering_details','needs_av','av_requirements','additional_info','status'];
    protected $table = 'event_bookings';
    protected function casts(): array { return ['event_date'=>'date','needs_catering'=>'boolean','needs_av'=>'boolean','expected_attendees'=>'integer','duration_days'=>'integer']; }
    protected static function booted(): void { static::creating(fn($b)=>$b->reference??='KICC-EVT-'.strtoupper(Str::random(10))); }
}