<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stream_destinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_stream_id')->constrained('live_streams')->cascadeOnDelete();
            $table->unsignedBigInteger('destinable_id');
            $table->string('destinable_type', 100); // App\Models\Screen or App\Models\ScreenGroup
            $table->string('status', 20)->default('pending'); // pending, active, ended, failed
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['live_stream_id', 'status']);
            $table->index(['destinable_type', 'destinable_id']);
        });

        Schema::create('trader_spotlights', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('trader_type', 32)->default('trader'); // trader, sacco, cooperative, farmer
            $table->unsignedBigInteger('institution_id')->nullable()->index();
            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->unsignedBigInteger('county_id')->nullable()->index();
            $table->string('contact_name')->nullable();
            $table->string('contact_mobile')->nullable();
            $table->string('contact_whatsapp')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('department_lead')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('spotlight_video_id')->nullable();
            $table->integer('duration_seconds')->default(45);
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_published')->default(true);
            $table->json('trade_info')->nullable();
            $table->timestamps();
        });

        Schema::create('screen_playlist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('screen_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('media_asset_id')->nullable()->index();
            $table->string('content_type', 32)->default('video'); // video, image, audio, live_feed, slide
            $table->integer('sort_order')->default(0);
            $table->integer('duration_seconds')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('overlay_data')->nullable(); // text overlays, contact cards, etc
            $table->timestamps();

            $table->index(['screen_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('screen_playlist_items');
        Schema::dropIfExists('trader_spotlights');
        Schema::dropIfExists('stream_destinations');
    }
};