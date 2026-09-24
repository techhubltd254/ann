<?php

namespace App\Models\Pipeline;

use Illuminate\Database\Eloquent\Model;

class PipelineLicence extends Model
{
    protected $table = 'pipeline_licences';

    protected $fillable = [
        'pipeline_code',
        'licence_type',
        'reference_number',
        'issuing_authority',
        'status',
        'document_path',
        'notes',
        'approved_at',
        'approved_by',
        'expires_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
}