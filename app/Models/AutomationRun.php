<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutomationRun extends Model
{
    protected $fillable = [
        'node_key', 'node_name', 'workflow', 'status', 'trigger',
        'root_pipeline_ids', 'settled_pipeline_ids', 'failed_pipeline_ids',
        'settled_count', 'dlq_count', 'correlation_id', 'duration_ms',
        'error', 'triggered_by', 'started_at', 'finished_at',
    ];

    protected $casts = [
        'root_pipeline_ids' => 'array',
        'settled_pipeline_ids' => 'array',
        'failed_pipeline_ids' => 'array',
        'settled_count' => 'integer',
        'dlq_count' => 'integer',
        'duration_ms' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];
}
