<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pages')) {
            return;
        }

        // Pages (static content pages — about, mission, history, etc.)
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content')->nullable();
            $table->string('excerpt')->nullable();
            $table->string('featured_image')->nullable();
            $table->string('category', 50)->nullable(); // about, history, sustainability, transport, etc.
            $table->integer('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        // Team Members (Board + Management)
        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('title')->nullable();
            $table->text('bio')->nullable();
            $table->string('photo_url')->nullable();
            $table->string('category', 30)->default('board'); // board, management
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Timeline Events (History milestones)
        Schema::create('timeline_events', function (Blueprint $table) {
            $table->id();
            $table->string('year', 10);
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // FAQ Items
        Schema::create('faq_items', function (Blueprint $table) {
            $table->id();
            $table->string('question');
            $table->text('answer')->nullable();
            $table->string('category', 50)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        // Services & Amenities descriptions
        Schema::create('service_items', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('category', 50)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        // Video Gallery items
        Schema::create('video_items', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('video_url');
            $table->string('thumbnail_url')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_items');
        Schema::dropIfExists('service_items');
        Schema::dropIfExists('faq_items');
        Schema::dropIfExists('timeline_events');
        Schema::dropIfExists('team_members');
        Schema::dropIfExists('pages');
    }
};