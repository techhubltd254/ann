<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_booths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booth_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 12, 2);
            $table->decimal('discount', 12, 2)->default(0);
            $table->string('exhibitor_name')->nullable();
            $table->string('exhibitor_email')->nullable();
            $table->string('exhibitor_phone')->nullable();
            $table->json('requirements')->nullable();
            $table->timestamps();

            $table->unique(['booking_id', 'booth_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_booths');
    }
};
