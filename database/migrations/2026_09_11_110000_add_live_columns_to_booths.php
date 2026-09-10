<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('booths', function (Blueprint $table) {
            if (!Schema::hasColumn('booths', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()->after('id');
            }
            if (!Schema::hasColumn('booths', 'slug')) {
                $table->string('slug', 255)->unique()->nullable()->after('name');
            }
            if (!Schema::hasColumn('booths', 'thumbnail')) {
                $table->string('thumbnail', 500)->nullable()->after('images');
            }
            if (!Schema::hasColumn('booths', 'stream_status')) {
                $table->string('stream_status', 20)->default('offline')->after('status');
            }
            if (!Schema::hasColumn('booths', 'gps_lat')) {
                $table->decimal('gps_lat', 10, 7)->nullable()->after('virtual_tour_url');
            }
            if (!Schema::hasColumn('booths', 'gps_lng')) {
                $table->decimal('gps_lng', 10, 7)->nullable()->after('gps_lat');
            }
            if (!Schema::hasColumn('booths', 'physical_address')) {
                $table->string('physical_address', 500)->nullable()->after('gps_lng');
            }
            if (!Schema::hasColumn('booths', 'contact_phone')) {
                $table->string('contact_phone', 20)->nullable()->after('contact_email');
            }
            if (!Schema::hasColumn('booths', 'whatsapp')) {
                $table->string('whatsapp', 20)->nullable()->after('contact_phone');
            }
            if (!Schema::hasColumn('booths', 'social_links')) {
                $table->json('social_links')->nullable()->after('whatsapp');
            }
            if (!Schema::hasColumn('booths', 'collateral')) {
                $table->json('collateral')->nullable()->after('social_links');
            }
            if (!Schema::hasColumn('booths', 'meeting_slots')) {
                $table->json('meeting_slots')->nullable()->after('collateral');
            }
            if (!Schema::hasColumn('booths', 'exhibition_id')) {
                $table->foreignId('exhibition_id')->nullable()->constrained()->nullOnDelete()->after('id');
            }
            if (!Schema::hasColumn('booths', 'county_id')) {
                $table->foreignId('county_id')->nullable()->constrained()->nullOnDelete()->after('exhibition_id');
            }
            if (!Schema::hasColumn('booths', 'max_viewers')) {
                $table->integer('max_viewers')->nullable()->after('meeting_slots');
            }
            if (!Schema::hasColumn('booths', 'gallery')) {
                $table->json('gallery')->nullable()->after('thumbnail');
            }
            if (!Schema::hasColumn('booths', 'meta')) {
                $table->json('meta')->nullable()->after('max_viewers');
            }
            if (!Schema::hasColumn('booths', 'tagline')) {
                $table->string('tagline', 500)->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        // No down — adding columns only
    }
};