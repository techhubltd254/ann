<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected array $syncableTables = [
        'sectors', 'sector_entities', 'county_tourism_attractions', 'county_hotels',
        'county_farms', 'county_health_facilities', 'county_institutions',
        'county_transport', 'county_culture_sites', 'county_products',
        'exhibitions', 'venues', 'booths', 'screens', 'screen_images',
    ];

    public function up(): void
    {
        foreach ($this->syncableTables as $table) {
            if (!Schema::hasColumn($table, 'local_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->uuid('local_id')->nullable()->unique()->after('id');
                    $t->unsignedBigInteger('central_id')->nullable()->after('local_id');
                    $t->string('sync_status', 32)->default('pending')->after('updated_at');
                    $t->string('content_hash', 64)->nullable()->after('sync_status');
                    $t->timestamp('synced_at')->nullable()->after('content_hash');
                    $t->index('sync_status');
                });
            }
        }

        if (!Schema::hasTable('sync_log')) {
            Schema::create('sync_log', function (Blueprint $t) {
                $t->id();
                $t->string('table_name', 128);
                $t->unsignedBigInteger('row_id')->nullable();
                $t->uuid('local_id')->nullable();
                $t->string('direction', 8)->default('push');
                $t->string('result', 32)->default('pending');
                $t->text('payload')->nullable();
                $t->text('response')->nullable();
                $t->text('conflict_reason')->nullable();
                $t->string('sync_key_id', 64)->nullable();
                $t->foreignId('user_id')->nullable()->constrained();
                $t->timestamp('created_at')->useCurrent();
                $t->index(['table_name', 'result']);
                $t->index('created_at');
            });
        }

        if (!Schema::hasTable('sync_keys')) {
            Schema::create('sync_keys', function (Blueprint $t) {
                $t->id();
                $t->string('county_slug', 64);
                $t->string('key_id', 64)->unique();
                $t->string('key_hmac', 128);
                $t->boolean('is_revoked')->default(false);
                $t->timestamp('revoked_at')->nullable();
                $t->timestamp('created_at')->useCurrent();
                $t->index('county_slug');
                $t->index('is_revoked');
            });
        }

        if (!Schema::hasTable('dashboard_requests')) {
            Schema::create('dashboard_requests', function (Blueprint $t) {
                $t->id();
                $t->string('company_name', 255);
                $t->string('contact_email', 255);
                $t->text('requested_modules');
                $t->text('branding_config')->nullable();
                $t->text('notes')->nullable();
                $t->string('status', 32)->default('pending');
                $t->text('rejection_reason')->nullable();
                $t->timestamp('approved_at')->nullable();
                $t->timestamp('provisioned_at')->nullable();
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('dashboard_templates')) {
            Schema::create('dashboard_templates', function (Blueprint $t) {
                $t->id();
                $t->string('name', 255);
                $t->string('slug', 128)->unique();
                $t->text('feature_flags');
                $t->text('description')->nullable();
                $t->boolean('is_active')->default(true);
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('dashboard_provisions')) {
            Schema::create('dashboard_provisions', function (Blueprint $t) {
                $t->id();
                $t->foreignId('dashboard_request_id')->constrained();
                $t->foreignId('template_id')->nullable()->constrained('dashboard_templates');
                $t->string('version', 32)->default('1.0.0');
                $t->text('manifest')->nullable();
                $t->timestamp('downloaded_at')->nullable();
                $t->timestamps();
            });
        }

        // Section 4: Sector/Tile/Media CMS
        if (!Schema::hasTable('county_sectors')) {
            Schema::create('county_sectors', function (Blueprint $t) {
                $t->id();
                $t->foreignId('county_id')->constrained()->cascadeOnDelete();
                $t->string('name', 255);
                $t->string('slug', 128);
                $t->unsignedSmallInteger('display_order')->default(0);
                $t->boolean('is_active')->default(true);
                $t->uuid('local_id')->nullable();
                $t->string('sync_status', 32)->default('synced');
                $t->string('content_hash', 64)->nullable();
                $t->timestamps();
                $t->unique(['county_id', 'slug']);
            });
        }

        if (!Schema::hasTable('sector_tiles')) {
            Schema::create('sector_tiles', function (Blueprint $t) {
                $t->id();
                $t->foreignId('county_sector_id')->constrained()->cascadeOnDelete();
                $t->string('title', 255);
                $t->string('tile_type', 32)->default('image');
                $t->unsignedSmallInteger('display_order')->default(0);
                $t->text('content')->nullable();
                $t->boolean('is_active')->default(true);
                $t->uuid('local_id')->nullable();
                $t->string('sync_status', 32)->default('synced');
                $t->string('content_hash', 64)->nullable();
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('tile_media')) {
            Schema::create('tile_media', function (Blueprint $t) {
                $t->id();
                $t->foreignId('sector_tile_id')->constrained()->cascadeOnDelete();
                $t->string('media_type', 32)->default('image');
                $t->string('file_path', 512);
                $t->string('r2_url', 512)->nullable();
                $t->string('resolution_tag', 32)->default('standard');
                $t->unsignedInteger('duration_seconds')->nullable();
                $t->boolean('is_primary')->default(false);
                $t->uuid('local_id')->nullable();
                $t->string('content_hash', 64)->nullable();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tile_media');
        Schema::dropIfExists('sector_tiles');
        Schema::dropIfExists('county_sectors');
        Schema::dropIfExists('dashboard_provisions');
        Schema::dropIfExists('dashboard_templates');
        Schema::dropIfExists('dashboard_requests');
        Schema::dropIfExists('sync_keys');
        Schema::dropIfExists('sync_log');

        foreach ($this->syncableTables as $table) {
            if (Schema::hasColumn($table, 'local_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn(['local_id', 'central_id', 'sync_status', 'content_hash', 'synced_at']);
                });
            }
        }
    }
};
