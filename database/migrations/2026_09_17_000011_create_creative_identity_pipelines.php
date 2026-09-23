<?php

/*
|--------------------------------------------------------------------------
| Pipeline tables — cluster: creative_identity
|--------------------------------------------------------------------------
| Generated for the KICC Pipeline Kit. Every table is guarded with
| Schema::hasTable() so this migration is IDEMPOTENT and non-destructive:
| existing tables (including anything already live in TiDB) are never touched.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // O1 Creative & Livestream Economy
        if (!Schema::hasTable('stream_sessions')) {
            Schema::create('stream_sessions', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('creator_user_id')->index()->nullable();
            $table->string('title');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->unsignedBigInteger('viewer_peak')->default(0);
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // O1 paid passes
        if (!Schema::hasTable('stream_passes')) {
            Schema::create('stream_passes', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('stream_session_id')->index();
            $table->unsignedBigInteger('user_id')->index()->nullable();
            $table->decimal('price', 18, 2)->default(0);
            $table->decimal('commission', 18, 2)->default(0);
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // O1 tips
        if (!Schema::hasTable('stream_tips')) {
            Schema::create('stream_tips', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('stream_session_id')->index();
            $table->unsignedBigInteger('from_user_id')->index()->nullable();
            $table->decimal('amount', 18, 2)->default(0);
            $table->decimal('commission', 18, 2)->default(0);
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // O2 Sports Talent & Events
        if (!Schema::hasTable('sports_talent_profiles')) {
            Schema::create('sports_talent_profiles', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('athlete_user_id')->index()->nullable();
            $table->string('sport')->nullable();
            $table->string('club')->nullable();
            $table->decimal('market_value', 18, 2)->nullable();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // O2 deals
        if (!Schema::hasTable('sports_deals')) {
            Schema::create('sports_deals', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('sports_talent_profile_id')->index();
            $table->unsignedBigInteger('club_user_id')->index()->nullable();
            $table->decimal('deal_value', 18, 2)->default(0);
            $table->decimal('success_fee', 18, 2)->default(0);
            $table->dateTime('closed_at')->nullable();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // P1 KYB-as-a-Service
        if (!Schema::hasTable('kyb_verification_requests')) {
            Schema::create('kyb_verification_requests', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('business_user_id')->index()->nullable();
            $table->string('requested_by')->nullable();
            $table->string('result')->nullable();
            $table->decimal('fee_charged', 18, 2)->default(0);
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stream_sessions');
        Schema::dropIfExists('stream_passes');
        Schema::dropIfExists('stream_tips');
        Schema::dropIfExists('sports_talent_profiles');
        Schema::dropIfExists('sports_deals');
        Schema::dropIfExists('kyb_verification_requests');
    }
};
