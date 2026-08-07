<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('counties', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('capital', 100);
            $table->string('code', 10)->unique();
            $table->string('former_province', 50);
            $table->string('economic_zone', 50);
            $table->integer('population_2024')->nullable();
            $table->float('area_km2')->nullable();
            $table->decimal('latitude', 10, 6)->nullable();
            $table->decimal('longitude', 10, 6)->nullable();
            $table->string('weather_station_id', 50)->nullable();
            $table->json('primary_sectors')->nullable();
            $table->string('icon_emoji', 10)->nullable();
            $table->string('tagline', 200)->nullable();
            $table->text('description')->nullable();
            $table->json('tourism_highlights')->nullable();
            $table->string('warmest_month', 50)->nullable();
            $table->string('coolest_month', 50)->nullable();
            $table->string('rainy_season', 100)->nullable();
            $table->string('dry_season', 100)->nullable();
            $table->string('slug', 100)->unique();
            $table->json('weather_tags')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('counties');
    }
};
