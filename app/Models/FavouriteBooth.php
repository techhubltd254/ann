<?php
namespace App\Models;

use App\Models\Booth;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class FavouriteBooth extends Model
{
    protected $fillable = ['user_id', 'booth_id', 'notify_on_live'];

    protected function casts(): array
    {
        return ['notify_on_live' => 'boolean'];
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function booth(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Booth::class);
    }
}