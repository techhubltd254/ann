<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaDerivative extends Model
{
    protected $fillable = [
        'media_asset_id', 'kind', 'path', 'mime', 'size_bytes', 'width', 'height', 'variant', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'meta' => 'json',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class);
    }
}
