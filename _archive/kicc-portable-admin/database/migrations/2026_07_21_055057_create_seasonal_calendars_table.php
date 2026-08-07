<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seasonal_calendars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('county_id')->constrained()->cascadeOnDelete();
            $table->integer('month');
            $table->float('avg_temp_c')->nullable();
            $table->float('rainfall_mm')->nullable();
            $table->string('tourism_season', 20)->nullable();
            $table->string('agri_season', 30)->nullable();
            $table->string('weather_tag', 30)->nullable();
            $table->unique(['county_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seasonal_calendars');
    }
};
