<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enquiry extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

    public function record(): BelongsTo
    {
        return $this->belongsTo(Record::class);
    }
}
