<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records-based publishing domain, ported from the legacy KICC admin (kicc-v2 branch).
 *
 * COLLISION-SAFE: this migration deliberately does NOT create or alter the existing
 * `media_assets` table (which the current platform already owns with a different
 * schema). Legacy admin media is stored in a new `record_media` table instead.
 * It also does not add `users.is_admin`; the ported admin reuses the current
 * role-based gate (`admin:kicc`).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('records')) {
            Schema::create('records', function (Blueprint $t) {
                $t->uuid('id')->primary();
                $t->string('type', 32)->index();
                $t->string('slug', 180);
                $t->string('name', 240);
                $t->longText('description')->nullable();
                $t->string('status', 16)->default('draft')->index();
                $t->json('payload')->nullable();
                $t->uuid('parent_id')->nullable()->index();
                $t->unsignedInteger('revision')->default(1);
                $t->unsignedBigInteger('updated_by')->nullable()->index();
                $t->timestamps();
                $t->unique(['type', 'slug']);
            });
        }

        if (!Schema::hasTable('record_media')) {
            Schema::create('record_media', function (Blueprint $t) {
                $t->uuid('id')->primary();
                $t->uuid('record_id')->index();
                $t->string('disk', 16);
                $t->string('path');
                $t->string('original_name');
                $t->string('mime', 120);
                $t->unsignedBigInteger('bytes');
                $t->string('title', 240);
                $t->text('description')->nullable();
                $t->string('status', 16)->default('draft')->index();
                $t->string('format', 16)->default('standard');
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('audit_events')) {
            Schema::create('audit_events', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('user_id')->nullable()->index();
                $t->string('action', 80);
                $t->string('subject_id', 64)->nullable();
                $t->json('details')->nullable();
                $t->timestamp('created_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('enquiries')) {
            Schema::create('enquiries', function (Blueprint $t) {
                $t->uuid('id')->primary();
                $t->uuid('record_id')->index();
                $t->string('name', 180);
                $t->string('email', 240);
                $t->string('phone', 60)->nullable();
                $t->text('message');
                $t->string('status', 16)->default('new')->index();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('enquiries');
        Schema::dropIfExists('audit_events');
        Schema::dropIfExists('record_media');
        Schema::dropIfExists('records');
    }
};
