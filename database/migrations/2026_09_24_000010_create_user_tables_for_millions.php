<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── User scalability indexes for million+ users ──
        $this->idxIfNotExists('users', 'idx_users_email', ['email']);
        $this->idxIfNotExists('users', 'idx_users_phone', ['phone']);
        $this->idxIfNotExists('users', 'idx_users_account_type', ['account_type']);
        $this->idxIfNotExists('users', 'idx_users_county', ['county_id']);
        $this->idxIfNotExists('users', 'idx_users_status', ['status']);
        $this->idxIfNotExists('users', 'idx_users_created', ['created_at']);

        // ── User addresses (Amazon-style multiple addresses per user) ──
        if (! Schema::hasTable('user_addresses')) {
            Schema::create('user_addresses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('label', 50)->nullable();            // "Home", "Work"
                $table->string('recipient_name', 200);
                $table->string('phone', 30);
                $table->string('county', 100);
                $table->string('town', 100);
                $table->string('address', 300);
                $table->string('landmark', 200)->nullable();
                $table->boolean('is_default')->default(false);
                $table->timestamps();
                $table->index(['user_id', 'is_default']);
            });
        }

        // ── User preferences for recommendations ──
        if (! Schema::hasTable('user_preferences')) {
            Schema::create('user_preferences', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->json('preferred_sectors')->nullable();      // ["agriculture","tourism"]
                $table->json('preferred_counties')->nullable();     // ["nairobi","mombasa"]
                $table->boolean('email_notifications')->default(true);
                $table->boolean('sms_notifications')->default(true);
                $table->boolean('marketing_emails')->default(false);
                $table->string('language', 10)->default('en');
                $table->timestamps();
                $table->unique('user_id');
            });
        }

        // ── Login history for security ──
        if (! Schema::hasTable('login_history')) {
            Schema::create('login_history', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 500)->nullable();
                $table->string('device', 100)->nullable();          // "mobile","desktop"
                $table->string('location', 200)->nullable();        // from CF-IPCity
                $table->boolean('successful')->default(true);
                $table->timestamp('login_at')->useCurrent();
                $table->index(['user_id', 'login_at']);
                $table->index('login_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('login_history');
        Schema::dropIfExists('user_preferences');
        Schema::dropIfExists('user_addresses');
    }

    private function idxIfNotExists(string $table, string $name, array $cols): void
    {
        if (! Schema::hasTable($table)) return;
        try {
            $existing = DB::select("SHOW INDEXES FROM {$table} WHERE Key_name = ?", [$name]);
            if (empty($existing)) {
                $colsSql = implode(', ', $cols);
                DB::statement("ALTER TABLE {$table} ADD INDEX {$name} ({$colsSql})");
            }
        } catch (\Throwable) {}
    }
};