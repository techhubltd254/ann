<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('phone_verification_codes', function (Blueprint $table) {
            // Column now stores email addresses too (email-based verification codes)
            $table->string('phone', 255)->change();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('phone_verification_codes', function (Blueprint $table) {
            $table->string('phone', 20)->change();
        });
    }
};
