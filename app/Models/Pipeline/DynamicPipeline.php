<?php

namespace App\Models\Pipeline;

use Illuminate\Database\Eloquent\Model;

class DynamicPipeline extends Model
{
    protected $table = 'dynamic_pipelines';

    protected $fillable = [
        'code', 'name', 'slug', 'sector', 'description',
        'mechanism', 'fee_rate', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'fee_rate' => 'float',
    ];
}