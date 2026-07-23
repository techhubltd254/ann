<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('county_user', function (Blueprint $table) {
            $table->foreignId('county_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 50)->default('admin');
            $table->timestamps();
            $table->primary(['county_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('county_user');
    }
};
