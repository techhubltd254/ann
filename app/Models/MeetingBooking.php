<?php
namespace App\Models;

use App\Models\Booth;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MeetingBooking extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'booth_id', 'exhibitor_user_id', 'visitor_user_id',
        'visitor_name', 'visitor_email', 'visitor_phone',
        'slot_start', 'slot_end', 'status',
        'notes', 'calendar_token',
    ];

    protected function casts(): array
    {
        return [
            'slot_start' => 'datetime',
            'slot_end' => 'datetime',
        ];
    }

    public function booth(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Booth::class);
    }

    public function exhibitor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'exhibitor_user_id');
    }

    public function visitor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'visitor_user_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function confirm(): void
    {
        $this->update(['status' => 'confirmed']);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }
}