<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The phone_verification_codes.code column was created as varchar(6) for plaintext
 * OTPs, but AuthController stores Hash::make($code) (60-char bcrypt) — every
 * send-code request 500'd with "Data too long for column 'code'".
 * Widened in prod on 2026-08-03; this migration makes the fix reproducible.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE phone_verification_codes MODIFY code varchar(255) NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE phone_verification_codes MODIFY code varchar(6) NOT NULL');
    }
};
