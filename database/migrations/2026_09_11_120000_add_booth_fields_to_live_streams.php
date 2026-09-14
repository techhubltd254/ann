<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // IDEMPOTENT_GUARD: skip when table missing / changes already applied
        if (!Schema::hasTable('live_streams')) {
            return;
        }
        try {
        Schema::table('live_streams', function (Blueprint $table) {
            if (!Schema::hasColumn('live_streams', 'booth_id')) {
                $table->unsignedBigInteger('booth_id')->nullable()->after('id');
                $table->index('booth_id');
            }
            if (!Schema::hasColumn('live_streams', 'cloudflare_uid')) {
                $table->string('cloudflare_uid', 128)->nullable()->unique();
            }
            if (!Schema::hasColumn('live_streams', 'is_live')) {
                $table->boolean('is_live')->default(false);
            }
        });
    
        } catch (\Throwable $e) {
            // already applied — ignore
        }
}

    public function down(): void
    {
        Schema::table('live_streams', function (Blueprint $table) {
            $table->dropColumn(['booth_id', 'cloudflare_uid', 'is_live']);
        });
    }
};