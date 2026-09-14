<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // IDEMPOTENT_GUARD: skip when table missing / changes already applied
        if (!Schema::hasTable('booths')) {
            return;
        }
        try {
        // ── Booth: contact + layout + spotlight fields ──
        Schema::table('booths', function (Blueprint $table) {
            $table->string('contact_name')->nullable()->after('location_hint');
            $table->string('contact_mobile')->nullable();
            $table->string('contact_whatsapp')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('department_lead')->nullable();
            $table->string('trader_type', 32)->nullable()->index(); // trader/sacco/cooperative/farmer
            $table->boolean('is_verified_trader')->default(false);
            $table->unsignedBigInteger('spotlight_video_id')->nullable();
            $table->unsignedBigInteger('floor_plan_id')->nullable()->index();
            $table->decimal('position_x', 8, 3)->nullable();
            $table->decimal('position_y', 8, 3)->nullable();
            $table->decimal('position_z', 8, 3)->nullable();
            $table->string('virtual_tour_url', 500)->nullable();
            $table->json('interactive_assets')->nullable();
        });

        // ── Screen: group + live feed + touch fields ──
        Schema::table('screens', function (Blueprint $table) {
            $table->unsignedBigInteger('group_id')->nullable()->index();
            $table->unsignedBigInteger('live_feed_id')->nullable();
            $table->boolean('is_touch')->default(false);
            $table->string('terminal_type', 32)->nullable();
        });

        // ── LiveStream: scheduling + distribution fields ──
        Schema::table('live_streams', function (Blueprint $table) {
            $table->timestamp('scheduled_start_at')->nullable();
            $table->timestamp('scheduled_end_at')->nullable();
            $table->string('stream_key', 255)->nullable();
            $table->string('rtmp_url', 500)->nullable();
        });

        // ── County: trade hub + contact fields ──
        Schema::table('counties', function (Blueprint $table) {
            $table->string('contact_commissioner_name')->nullable();
            $table->string('contact_commissioner_phone')->nullable();
            $table->string('contact_governor_phone')->nullable();
            $table->string('contact_investment_desk_email')->nullable();
            $table->string('whatsapp_business')->nullable();
            $table->decimal('trade_volume_ksh', 16, 2)->nullable();
            $table->json('top_export_products')->nullable();
            $table->json('investment_opportunities')->nullable();
        });

        // ── CountyInstitution: trader + voice note fields ──
        Schema::table('county_institutions', function (Blueprint $table) {
            $table->boolean('is_verified_trader')->default(false)->after('is_published');
            $table->string('trader_type', 32)->nullable();
            $table->unsignedBigInteger('spotlight_video_id')->nullable();
            $table->string('whatsapp')->nullable()->after('phone');
            $table->json('department_leads')->nullable();
        });

        // ── Product: trade fields ──
        Schema::table('products', function (Blueprint $table) {
            $table->integer('moq')->nullable()->after('unit');
            $table->decimal('fob_price', 12, 2)->nullable();
            $table->string('incoterm', 10)->nullable();
            $table->string('hs_code', 20)->nullable();
            $table->boolean('export_readiness')->default(false);
            $table->json('certifications')->nullable();
            $table->string('trade_enquiry_email')->nullable();
            $table->boolean('is_spotlight_product')->default(false);
        });
    
        } catch (\Throwable $e) {
            // already applied — ignore
        }
}

    public function down(): void
    {
        Schema::table('booths', function (Blueprint $table) {
            $table->dropColumn([
                'contact_name','contact_mobile','contact_whatsapp','contact_email','department_lead',
                'trader_type','is_verified_trader','spotlight_video_id','floor_plan_id',
                'position_x','position_y','position_z','virtual_tour_url','interactive_assets',
            ]);
        });
        Schema::table('screens', function (Blueprint $table) {
            $table->dropColumn(['group_id','live_feed_id','is_touch','terminal_type']);
        });
        Schema::table('live_streams', function (Blueprint $table) {
            $table->dropColumn(['scheduled_start_at','scheduled_end_at','stream_key','rtmp_url']);
        });
        Schema::table('counties', function (Blueprint $table) {
            $table->dropColumn([
                'contact_commissioner_name','contact_commissioner_phone','contact_governor_phone',
                'contact_investment_desk_email','whatsapp_business','trade_volume_ksh',
                'top_export_products','investment_opportunities',
            ]);
        });
        Schema::table('county_institutions', function (Blueprint $table) {
            $table->dropColumn([
                'is_verified_trader','trader_type','spotlight_video_id','whatsapp','department_leads',
            ]);
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'moq','fob_price','incoterm','hs_code','export_readiness','certifications',
                'trade_enquiry_email','is_spotlight_product',
            ]);
        });
    }
};