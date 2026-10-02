<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlJournalEntry extends Model
{
    protected $table = 'gl_journal_entries';
    protected $fillable = ['*'];
}
