<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phone_verification_codes', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20);
            $table->string('code', 6);
            $table->string('purpose')->default('registration');
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->integer('attempts')->default(0);
            $table->timestamps();

            $table->index(['phone', 'code', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_verification_codes');
    }
};
