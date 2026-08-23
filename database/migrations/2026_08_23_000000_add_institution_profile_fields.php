<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Institution profile fields for the master-record + auto-sync system
        Schema::table('county_institutions', function (Blueprint $table) {
            $table->string('slug')->nullable()->index();
            $table->foreignId('user_id')->nullable()->index(); // binds the institution's admin login
            $table->string('logo_url')->nullable();
            $table->string('cover_image_url')->nullable();
            $table->string('headquarters')->nullable();
            $table->integer('founded_year')->nullable();
            $table->decimal('lat', 10, 6)->nullable();
            $table->decimal('lng', 10, 6)->nullable();
            $table->json('social_links')->nullable();
            $table->longText('story')->nullable();
            $table->json('production_chain')->nullable();   // [{step, description}]
            $table->json('sector_mappings')->nullable();    // [{sector_slug, entry_name, entry_type, entry_fee, description}]
            $table->json('products')->nullable();           // [{name, price, unit, category, description, image_url}]
            $table->json('videos')->nullable();             // [{title, description, path, mime, size_bytes, role, entity_key}]
            $table->timestamp('synced_at')->nullable();
        });

        // Bind a user to an institution (same pattern as county_id)
        if (!Schema::hasColumn('users', 'institution_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('institution_id')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        Schema::table('county_institutions', function (Blueprint $table) {
            $table->dropColumn([
                'slug', 'user_id', 'logo_url', 'cover_image_url', 'headquarters',
                'founded_year', 'lat', 'lng', 'social_links', 'story',
                'production_chain', 'sector_mappings', 'products', 'videos', 'synced_at',
            ]);
        });
        if (Schema::hasColumn('users', 'institution_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('institution_id');
            });
        }
    }
};
