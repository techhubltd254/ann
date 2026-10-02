<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LedgerTransaction extends Model
{
    protected $table = 'ledger_transactions';
    protected $fillable = ['*'];
}
