<?php

/*
|--------------------------------------------------------------------------
| Pipeline tables — cluster: trade_commerce
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
        // A2 Cross-Border Logistics
        if (!Schema::hasTable('logistics_shipments')) {
            Schema::create('logistics_shipments', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('user_id')->index()->nullable();
            $table->string('origin_country')->nullable();
            $table->string('destination_country')->nullable();
            $table->string('mode')->default('road');
            $table->decimal('declared_value', 18, 2)->default(0);
            $table->decimal('landed_cost', 18, 2)->default(0);
            $table->decimal('fx_spread', 5, 2)->default(0);
            $table->unsignedBigInteger('carrier_id')->index()->nullable();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // A2 carrier quotes
        if (!Schema::hasTable('logistics_carrier_quotes')) {
            Schema::create('logistics_carrier_quotes', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('logistics_shipment_id')->index();
            $table->string('carrier_name');
            $table->decimal('quote_amount', 18, 2);
            $table->unsignedBigInteger('transit_days')->nullable();
            $table->date('valid_until')->nullable();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // A3 Construction Materials Group-Buy
        if (!Schema::hasTable('groupbuy_campaigns')) {
            Schema::create('groupbuy_campaigns', function (Blueprint $table) {
                $table->id();
            $table->string('title');
            $table->string('category')->nullable();
            $table->unsignedBigInteger('target_qty')->nullable();
            $table->unsignedBigInteger('committed_qty')->default(0);
            $table->decimal('unit_price', 18, 2)->nullable();
            $table->dateTime('closes_at')->nullable();
            $table->decimal('rebate_share', 5, 2)->default(0);
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // A3 group-buy orders
        if (!Schema::hasTable('groupbuy_orders')) {
            Schema::create('groupbuy_orders', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('groupbuy_campaign_id')->index();
            $table->unsignedBigInteger('user_id')->index()->nullable();
            $table->unsignedBigInteger('quantity')->default(1);
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->decimal('fee_charged', 18, 2)->default(0);
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // A4 County Wholesale
        if (!Schema::hasTable('county_wholesale_listings')) {
            Schema::create('county_wholesale_listings', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('county_id')->index();
            $table->unsignedBigInteger('seller_id')->index()->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('unit_price', 18, 2)->nullable();
            $table->unsignedBigInteger('min_order_qty')->default(1);
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // A4 Market-Day Trade
        if (!Schema::hasTable('market_day_sales')) {
            Schema::create('market_day_sales', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('county_id')->index();
            $table->date('market_day_date')->nullable();
            $table->unsignedBigInteger('seller_id')->index()->nullable();
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->decimal('market_fee', 18, 2)->default(0);
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // A5 Franchise & Dealership
        if (!Schema::hasTable('franchise_listings')) {
            Schema::create('franchise_listings', function (Blueprint $table) {
                $table->id();
            $table->string('franchisor');
            $table->string('industry')->nullable();
            $table->decimal('investment_min', 18, 2)->nullable();
            $table->string('territory')->nullable();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // A5 matches with success fee
        if (!Schema::hasTable('franchise_matches')) {
            Schema::create('franchise_matches', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('franchise_listing_id')->index();
            $table->unsignedBigInteger('investor_user_id')->index()->nullable();
            $table->decimal('match_score', 5, 2)->default(0);
            $table->decimal('success_fee', 18, 2)->default(0);
            $table->dateTime('signed_at')->nullable();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // A6 SME Acquisition
        if (!Schema::hasTable('sme_business_listings')) {
            Schema::create('sme_business_listings', function (Blueprint $table) {
                $table->id();
            $table->string('business_name');
            $table->string('industry')->nullable();
            $table->decimal('asking_price', 18, 2)->nullable();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('financials')->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // A6 deals
        if (!Schema::hasTable('sme_acquisition_deals')) {
            Schema::create('sme_acquisition_deals', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('sme_business_listing_id')->index();
            $table->unsignedBigInteger('buyer_user_id')->index()->nullable();
            $table->decimal('deal_amount', 18, 2)->default(0);
            $table->decimal('success_fee', 18, 2)->default(0);
            $table->dateTime('closed_at')->nullable();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('logistics_shipments');
        Schema::dropIfExists('logistics_carrier_quotes');
        Schema::dropIfExists('groupbuy_campaigns');
        Schema::dropIfExists('groupbuy_orders');
        Schema::dropIfExists('county_wholesale_listings');
        Schema::dropIfExists('market_day_sales');
        Schema::dropIfExists('franchise_listings');
        Schema::dropIfExists('franchise_matches');
        Schema::dropIfExists('sme_business_listings');
        Schema::dropIfExists('sme_acquisition_deals');
    }
};
