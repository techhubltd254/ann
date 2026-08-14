<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('phone_verification_codes', function (Blueprint $table) {
            // Column now stores email addresses too (email-based verification codes)
            $table->string('phone', 255)->change();
        });
    }

    public function down(): void
    {
        Schema::table('phone_verification_codes', function (Blueprint $table) {
            $table->string('phone', 20)->change();
        });
    }
};
