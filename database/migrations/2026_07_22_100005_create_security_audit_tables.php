<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_mfa_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('secret')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('email')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('oauth_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('client_id', 80)->unique();
            $table->string('client_secret', 80);
            $table->text('redirect_uri');
            $table->text('allowed_scopes')->nullable();
            $table->boolean('is_confidential')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('oauth_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('oauth_client_id')->constrained()->cascadeOnDelete();
            $table->string('access_token', 255)->unique();
            $table->string('refresh_token', 255)->nullable()->unique();
            $table->text('scopes')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 100);
            $table->string('resource_type', 50)->nullable();
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->index(['resource_type', 'resource_id']);
        });

        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel', 30);
            $table->string('type', 50);
            $table->text('message');
            $table->string('status', 20)->default('sent');
            $table->string('provider_reference')->nullable();
            $table->timestamp('sent_at');
            $table->timestamps();
        });

        Schema::create('advertisements', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type', 30);
            $table->string('placement', 50);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('target_url')->nullable();
            $table->string('image_url')->nullable();
            $table->decimal('budget', 10, 2)->nullable();
            $table->decimal('spent', 10, 2)->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('impressions')->default(0);
            $table->integer('clicks')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advertisements');
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('oauth_tokens');
        Schema::dropIfExists('oauth_clients');
        Schema::dropIfExists('user_mfa_devices');
    }
};
