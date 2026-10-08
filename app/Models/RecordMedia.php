<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Media owned by a legacy-admin Record. Deliberately a separate table/model from
 * the platform's own MediaAsset so neither admin can clobber the other's files.
 */
class RecordMedia extends Model
{
    use HasUuids;

    protected $table = 'record_media';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['bytes' => 'integer'];
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(Record::class);
    }

    public function isVideo(): bool
    {
        return str_starts_with((string) $this->mime, 'video/');
    }

    public function url(): string
    {
        return route('admin.media.show', $this);
    }
}
