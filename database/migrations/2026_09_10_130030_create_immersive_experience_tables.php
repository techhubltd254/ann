<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // IDEMPOTENT_GUARD: table may already exist on TiDB (raw schema)
        if (Schema::hasTable('housing_projects')) {
            return;
        }

        Schema::create('housing_projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedBigInteger('county_id')->nullable()->index();
            $table->string('location')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('project_type', 32)->default('affordable'); // affordable, social, market-rate
            $table->integer('total_units')->nullable();
            $table->integer('completed_units')->nullable();
            $table->unsignedBigInteger('flythrough_video_id')->nullable();
            $table->unsignedBigInteger('splat_asset_id')->nullable();
            $table->json('beneficiary_audio_ids')->nullable();
            $table->json('amenities')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('drone_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedBigInteger('county_id')->nullable()->index();
            $table->string('location')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedBigInteger('drone_video_id')->nullable();
            $table->unsignedBigInteger('audio_overlay_id')->nullable(); // presidential audio
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('presidential_audios', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('speaker', 100)->default('President');
            $table->text('transcript')->nullable();
            $table->unsignedBigInteger('audio_asset_id')->nullable();
            $table->json('key_topics')->nullable();
            $table->json('timemarks')->nullable(); // [{start, end, topic}]
            $table->timestamps();
        });

        Schema::create('floor_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exhibition_id')->nullable()->index();
            $table->unsignedBigInteger('venue_id')->nullable()->index();
            $table->string('name');
            $table->string('image_url', 500)->nullable();
            $table->json('layout_data')->nullable(); // {width, height, booths:[{id,x,y,w,h,rotation}]}
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('floor_plans');
        Schema::dropIfExists('presidential_audios');
        Schema::dropIfExists('drone_sequences');
        Schema::dropIfExists('housing_projects');
    }
};