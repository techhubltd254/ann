<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('screen_images', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->text('storage_path');
            $table->text('original_path');
            $table->string('county_id', 50)->nullable()->index();
            $table->string('sector_ids', 255)->default('');
            $table->string('tags', 255)->default('');
            $table->float('quality_score')->default(0.5);
            $table->string('scene_type', 50)->default('unknown');
            $table->integer('width')->default(0);
            $table->integer('height')->default(0);
            $table->float('brightness')->default(0.5);
            $table->float('contrast')->default(0.3);
            $table->boolean('has_water')->default(false);
            $table->string('source', 50)->default('upload');
            $table->timestamps();
        });

        Schema::create('screens', function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->string('label');
            $table->string('location', 100)->default('');
            $table->string('county_id', 50)->nullable()->index();
            $table->string('sector_id', 50)->nullable()->index();
            $table->integer('target_duration_sec')->default(60);
            $table->integer('min_images')->default(10);
            $table->integer('max_images')->default(30);
            $table->integer('refresh_interval_min')->default(60);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('screens');
        Schema::dropIfExists('screen_images');
    }
};
