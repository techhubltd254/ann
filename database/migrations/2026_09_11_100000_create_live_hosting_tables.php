<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Booth authorizations — Super Admin's live authorization list
        Schema::create('booth_authorizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booth_id')->constrained('booths')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('pending'); // AUTHORIZED, SUSPENDED, STOPPED, EXPIRED, PENDING
            $table->string('api_key', 128)->unique()->nullable();
            $table->timestamp('api_key_expires_at')->nullable();
            $table->string('reason_code', 50)->nullable();
            $table->text('reason_note')->nullable();
            $table->timestamp('authorized_at')->nullable();
            $table->timestamp('terminated_at')->nullable();
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->integer('missed_heartbeats')->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['booth_id', 'status']);
            $table->index(['user_id', 'status']);
        });

        // Heartbeat logs — immutable audit trail
        Schema::create('heartbeat_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booth_authorization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booth_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20);
            $table->string('session_id', 128);
            $table->json('payload')->nullable();
            $table->string('token_sent', 255)->nullable();
            $table->string('token_verified', 20)->nullable();
            $table->timestamp('heartbeat_at')->useCurrent();
            $table->timestamps();

            $table->index(['booth_authorization_id', 'created_at']);
            $table->index(['booth_id', 'created_at']);
            $table->index('session_id');
        });

        // Exhibitor studio sessions — tracks active studio connections
        Schema::create('exhibitor_studio_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booth_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booth_authorization_id')->constrained()->cascadeOnDelete();
            $table->string('session_token', 128)->unique();
            $table->string('stream_status', 20)->default('offline'); // offline, live, paused, slate
            $table->timestamp('session_started_at')->useCurrent();
            $table->timestamp('last_activity_at')->useCurrent();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['user_id', 'stream_status']);
            $table->index('session_token');
        });

        // Meeting bookings schedule
        Schema::create('meeting_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booth_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exhibitor_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('visitor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('visitor_name');
            $table->string('visitor_email');
            $table->string('visitor_phone', 20)->nullable();
            $table->timestamp('slot_start');
            $table->timestamp('slot_end');
            $table->string('status', 20)->default('pending'); // pending, confirmed, cancelled, completed
            $table->text('notes')->nullable();
            $table->string('calendar_token', 128)->nullable()->unique();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['booth_id', 'slot_start']);
            $table->index(['visitor_user_id', 'status']);
        });

        // Favourite booths (viewer bookmarks)
        Schema::create('favourite_booths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booth_id')->constrained()->cascadeOnDelete();
            $table->boolean('notify_on_live')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'booth_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favourite_booths');
        Schema::dropIfExists('meeting_bookings');
        Schema::dropIfExists('exhibitor_studio_sessions');
        Schema::dropIfExists('heartbeat_logs');
        Schema::dropIfExists('booth_authorizations');
    }
};