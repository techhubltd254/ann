<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentDocument extends Model
{
    protected $fillable = ['agent_id', 'document_type', 'file_path', 'original_name', 'status', 'notes', 'verified_at'];

    protected function casts(): array
    {
        return ['verified_at' => 'datetime'];
    }

    public function agent() { return $this->belongsTo(Agent::class); }
}