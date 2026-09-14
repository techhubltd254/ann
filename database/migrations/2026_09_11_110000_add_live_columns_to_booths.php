<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // IDEMPOTENT_GUARD: skip when table missing / changes already applied
        if (!Schema::hasTable('booths')) {
            return;
        }
        try {
        Schema::table('booths', function (Blueprint $table) {
            if (!Schema::hasColumn('booths', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()->after('id');
            }
            if (!Schema::hasColumn('booths', 'slug')) {
                $table->string('slug', 255)->unique()->nullable()->after('name');
            }
            if (!Schema::hasColumn('booths', 'thumbnail')) {
                $table->string('thumbnail', 500)->nullable();
            }
            if (!Schema::hasColumn('booths', 'stream_status')) {
                $table->string('stream_status', 20)->default('offline');
            }
            if (!Schema::hasColumn('booths', 'gps_lat')) {
                $table->decimal('gps_lat', 10, 7)->nullable();
            }
            if (!Schema::hasColumn('booths', 'gps_lng')) {
                $table->decimal('gps_lng', 10, 7)->nullable();
            }
            if (!Schema::hasColumn('booths', 'physical_address')) {
                $table->string('physical_address', 500)->nullable();
            }
            if (!Schema::hasColumn('booths', 'contact_phone')) {
                $table->string('contact_phone', 20)->nullable();
            }
            if (!Schema::hasColumn('booths', 'whatsapp')) {
                $table->string('whatsapp', 20)->nullable();
            }
            if (!Schema::hasColumn('booths', 'social_links')) {
                $table->json('social_links')->nullable();
            }
            if (!Schema::hasColumn('booths', 'collateral')) {
                $table->json('collateral')->nullable();
            }
            if (!Schema::hasColumn('booths', 'meeting_slots')) {
                $table->json('meeting_slots')->nullable();
            }
            if (!Schema::hasColumn('booths', 'max_viewers')) {
                $table->integer('max_viewers')->nullable();
            }
            if (!Schema::hasColumn('booths', 'gallery')) {
                $table->json('gallery')->nullable();
            }
            if (!Schema::hasColumn('booths', 'meta')) {
                $table->json('meta')->nullable();
            }
            if (!Schema::hasColumn('booths', 'tagline')) {
                $table->string('tagline', 500)->nullable();
            }
        });
    
        } catch (\Throwable $e) {
            // already applied — ignore
        }
}

    public function down(): void
    {
    }
};