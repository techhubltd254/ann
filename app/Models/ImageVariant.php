<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImageVariant extends Model
{
    protected $fillable = [
        'owner_type', 'owner_id', 'source_url', 'source_hash',
        'thumb_key', 'card_key', 'hero_key', 'blur', 'width', 'height',
    ];

    
    protected function casts(): array
    {
        return [
            'width' => 'integer',
            'height' => 'integer',
        ];
    }
public function thumbUrl(): ?string
    {
        return $this->thumb_key ? \App\Services\ImageOptimizer::variantUrl($this->thumb_key) : null;
    }

    public function cardUrl(): ?string
    {
        return $this->card_key ? \App\Services\ImageOptimizer::variantUrl($this->card_key) : null;
    }

    public function heroUrl(): ?string
    {
        return $this->hero_key ? \App\Services\ImageOptimizer::variantUrl($this->hero_key) : null;
    }

    public static function for(string $ownerType, int $ownerId): ?self
    {
        return static::where('owner_type', $ownerType)->where('owner_id', $ownerId)->latest('id')->first();
    }

    public static function byHash(string $hash): ?self
    {
        return static::where('source_hash', $hash)->latest('id')->first();
    }
}