<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sector_entities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('county_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sector_id')->constrained()->cascadeOnDelete();
            $table->morphs('entity');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('sector_type', 50);
            $table->string('capture_status', 10)->default('none');
            $table->string('sponsor_funder_tag')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->json('contact_info')->nullable();
            $table->json('social_links')->nullable();
            $table->boolean('is_published')->default(false);
            $table->string('language_primary', 5)->default('en');
            $table->json('tags')->nullable();
            $table->string('verification_owner')->nullable();
            $table->timestamp('verification_date')->nullable();
            $table->timestamps();
            $table->index(['county_id', 'sector_id']);
        });

        Schema::create('sector_entity_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sector_entity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('rating')->unsigned();
            $table->text('review')->nullable();
            $table->boolean('is_verified_purchase')->default(false);
            $table->timestamps();
        });

        Schema::create('entity_trust_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sector_entity_id')->constrained()->cascadeOnDelete();
            $table->integer('trust_score')->default(0);
            $table->integer('visibility_score')->default(0);
            $table->char('trust_grade', 1)->nullable();
            $table->boolean('is_sponsored')->default(false);
            $table->json('signal_breakdown')->nullable();
            $table->timestamp('calculated_at');
            $table->timestamps();
        });

        Schema::create('entity_media', function (Blueprint $table) {
            $table->id();
            $table->morphs('mediable');
            $table->string('type', 30);
            $table->string('url');
            $table->string('thumbnail_url')->nullable();
            $table->string('alt_text')->nullable();
            $table->integer('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('immersive_content_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sector_entity_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('county_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('tier', 10);
            $table->string('content_type', 30);
            $table->string('file_url');
            $table->string('preview_url')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('immersive_content_records');
        Schema::dropIfExists('entity_media');
        Schema::dropIfExists('entity_trust_scores');
        Schema::dropIfExists('sector_entity_reviews');
        Schema::dropIfExists('sector_entities');
    }
};
