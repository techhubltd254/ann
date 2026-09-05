<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DisputeCase extends Model
{
    protected $fillable = ['escrow_transaction_id', 'raised_by', 'reason', 'description', 'status', 'resolution', 'resolved_at', 'resolved_by'];

    
    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }
public function escrowTransaction() { return $this->belongsTo(EscrowTransaction::class); }
    public function raisedBy() { return $this->belongsTo(User::class, 'raised_by'); }
    public function resolvedBy() { return $this->belongsTo(User::class, 'resolved_by'); }
}