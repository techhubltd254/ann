<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pipeline extends Model
{
    protected $table = 'pipelines';
    protected $guarded = [];

    public function registrations()
    {
        return $this->hasMany(PipelineRegistration::class, 'pipeline_id');
    }
}
