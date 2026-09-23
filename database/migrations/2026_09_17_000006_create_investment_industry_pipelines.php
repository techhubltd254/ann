<?php

/*
|--------------------------------------------------------------------------
| Pipeline tables — cluster: investment_industry
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
        // D1 Industrial / SEZ Facilitation
        if (!Schema::hasTable('sez_investor_leads')) {
            Schema::create('sez_investor_leads', function (Blueprint $table) {
                $table->id();
            $table->string('investor_name');
            $table->string('origin_country')->nullable();
            $table->string('sector')->nullable();
            $table->decimal('ticket_size', 18, 2)->nullable();
            $table->string('sez_zone')->nullable();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // D1 deals with success fee
        if (!Schema::hasTable('sez_deals')) {
            Schema::create('sez_deals', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('sez_investor_lead_id')->index();
            $table->decimal('committed_capex', 18, 2)->default(0);
            $table->decimal('success_fee_pct', 5, 2)->default(0.3);
            $table->decimal('success_fee', 18, 2)->default(0);
            $table->dateTime('closed_at')->nullable();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // D2 SEZ Workforce Matching
        if (!Schema::hasTable('sez_workforce_requests')) {
            Schema::create('sez_workforce_requests', function (Blueprint $table) {
                $table->id();
            $table->string('sez_zone')->nullable();
            $table->string('role');
            $table->unsignedBigInteger('headcount')->default(1);
            $table->string('skills_tier')->nullable();
            $table->date('fill_by')->nullable();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // D2 placements
        if (!Schema::hasTable('sez_placements')) {
            Schema::create('sez_placements', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('sez_workforce_request_id')->index();
            $table->unsignedBigInteger('worker_user_id')->index()->nullable();
            $table->decimal('hire_fee', 18, 2)->default(0);
            $table->dateTime('placed_at')->nullable();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // D3 Real Estate (Ardhisasa-gated)
        if (!Schema::hasTable('real_estate_listings')) {
            Schema::create('real_estate_listings', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->string('title');
            $table->boolean('title_deed_verified')->default(false);
            $table->decimal('price', 18, 2)->nullable();
            $table->string('ardhisasa_ref')->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // D3 deals
        if (!Schema::hasTable('real_estate_deals')) {
            Schema::create('real_estate_deals', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('real_estate_listing_id')->index();
            $table->unsignedBigInteger('buyer_user_id')->index()->nullable();
            $table->decimal('deal_amount', 18, 2)->default(0);
            $table->decimal('facilitation_fee', 18, 2)->default(0);
            $table->dateTime('closed_at')->nullable();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // D4 Diaspora Investment Corridor
        if (!Schema::hasTable('diaspora_investment_projects')) {
            Schema::create('diaspora_investment_projects', function (Blueprint $table) {
                $table->id();
            $table->string('title');
            $table->string('country_of_origin')->nullable();
            $table->string('vehicle')->default('realestate');
            $table->decimal('target_raise', 18, 2)->default(0);
            $table->decimal('raised', 18, 2)->default(0);
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // D4 investments
        if (!Schema::hasTable('diaspora_investments')) {
            Schema::create('diaspora_investments', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('diaspora_investment_project_id')->index();
            $table->unsignedBigInteger('diaspora_user_id')->index()->nullable();
            $table->decimal('amount', 18, 2)->default(0);
            $table->decimal('fx_spread', 5, 2)->default(0);
            $table->decimal('commission', 18, 2)->default(0);
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // D5 Mining Value-Chain
        if (!Schema::hasTable('mining_listings')) {
            Schema::create('mining_listings', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->string('mineral');
            $table->unsignedBigInteger('quantity')->default(0);
            $table->string('unit')->default('tonne');
            $table->string('licence_no')->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // D5 deals
        if (!Schema::hasTable('mining_deals')) {
            Schema::create('mining_deals', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('mining_listing_id')->index();
            $table->unsignedBigInteger('buyer_user_id')->index()->nullable();
            $table->decimal('deal_amount', 18, 2)->default(0);
            $table->decimal('commission', 18, 2)->default(0);
            $table->json('royalty_split')->nullable();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sez_investor_leads');
        Schema::dropIfExists('sez_deals');
        Schema::dropIfExists('sez_workforce_requests');
        Schema::dropIfExists('sez_placements');
        Schema::dropIfExists('real_estate_listings');
        Schema::dropIfExists('real_estate_deals');
        Schema::dropIfExists('diaspora_investment_projects');
        Schema::dropIfExists('diaspora_investments');
        Schema::dropIfExists('mining_listings');
        Schema::dropIfExists('mining_deals');
    }
};
