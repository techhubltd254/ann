<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsentRecord extends Model
{
    protected $fillable = [
        'consent_form_id', 'signer_name', 'signer_id_number',
        'signer_phone', 'signer_email', 'agreements',
        'ip_address', 'signed_at',
    ];

    protected function casts(): array
    {
        return [
            'agreements' => 'array',
            'signed_at' => 'datetime',
        ];
    }

    public function consentForm()
    {
        return $this->belongsTo(ConsentForm::class);
    }
}