<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LedgerEntry extends Model
{
    protected $table = 'ledger_entries';
    protected $fillable = ['*'];
}
