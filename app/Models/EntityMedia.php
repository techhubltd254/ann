<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntityMedia extends Model
{
    protected $fillable = ['mediable_type', 'mediable_id', 'type', 'url', 'thumbnail_url', 'alt_text', 'sort_order', 'metadata'];

    protected function casts(): array
    {
        return [
            'metadata' => 'json',
            'sort_order' => 'integer',
        ];
    }

    public function mediable()
    {
        return $this->morphTo();
    }
}
