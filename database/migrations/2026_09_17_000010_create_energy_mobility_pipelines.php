<?php

/*
|--------------------------------------------------------------------------
| Pipeline tables — cluster: energy_mobility
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
        // M1 Clean-Energy Commerce
        if (!Schema::hasTable('energy_products')) {
            Schema::create('energy_products', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('vendor_user_id')->index()->nullable();
            $table->string('name');
            $table->string('category')->default('solar');
            $table->decimal('price', 18, 2)->nullable();
            $table->boolean('payg_enabled')->default(false);
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // M1 PAYG escrow contracts
        if (!Schema::hasTable('energy_payg_contracts')) {
            Schema::create('energy_payg_contracts', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('energy_product_id')->index();
            $table->unsignedBigInteger('customer_user_id')->index()->nullable();
            $table->decimal('total_price', 18, 2)->default(0);
            $table->unsignedBigInteger('installments')->default(12);
            $table->unsignedBigInteger('paid_installments')->default(0);
            $table->boolean('insurance_attached')->default(false);
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // M2 Carbon & Climate Assets
        if (!Schema::hasTable('carbon_projects')) {
            Schema::create('carbon_projects', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('developer_user_id')->index()->nullable();
            $table->string('project_name');
            $table->string('standard')->nullable();
            $table->unsignedBigInteger('credits_available')->default(0);
            $table->string('verification_status')->default('pending');
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // M2 trades
        if (!Schema::hasTable('carbon_credit_trades')) {
            Schema::create('carbon_credit_trades', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('carbon_project_id')->index();
            $table->unsignedBigInteger('buyer_user_id')->index()->nullable();
            $table->unsignedBigInteger('credits')->default(0);
            $table->decimal('price_per_credit', 18, 2)->default(0);
            $table->decimal('brokerage', 18, 2)->default(0);
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // M3 Water Infrastructure Matchmaking
        if (!Schema::hasTable('water_capex_projects')) {
            Schema::create('water_capex_projects', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->string('project_name');
            $table->decimal('capex_estimate', 18, 2)->default(0);
            $table->string('donor')->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // M3 deals
        if (!Schema::hasTable('water_project_deals')) {
            Schema::create('water_project_deals', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('water_capex_project_id')->index();
            $table->unsignedBigInteger('contractor_user_id')->index()->nullable();
            $table->decimal('committed_capex', 18, 2)->default(0);
            $table->decimal('success_fee', 18, 2)->default(0);
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // M4 Waste / Recycling Exchange
        if (!Schema::hasTable('waste_listings')) {
            Schema::create('waste_listings', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->string('waste_type');
            $table->unsignedBigInteger('quantity_kg')->default(0);
            $table->decimal('price_per_kg', 18, 2)->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // M4 transactions
        if (!Schema::hasTable('waste_transactions')) {
            Schema::create('waste_transactions', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('waste_listing_id')->index();
            $table->unsignedBigInteger('buyer_user_id')->index()->nullable();
            $table->decimal('amount', 18, 2)->default(0);
            $table->decimal('commission', 18, 2)->default(0);
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // N1 PSV / Matatu SACCO
        if (!Schema::hasTable('sacco_vehicles')) {
            Schema::create('sacco_vehicles', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('sacco_id')->index()->nullable();
            $table->string('plate_no')->nullable();
            $table->string('route')->nullable();
            $table->boolean('ntsa_inspected')->default(false);
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // N1 daily collections
        if (!Schema::hasTable('sacco_daily_collections')) {
            Schema::create('sacco_daily_collections', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('sacco_vehicle_id')->index();
            $table->date('collection_date')->nullable();
            $table->decimal('amount', 18, 2)->default(0);
            $table->decimal('collection_fee', 18, 2)->default(0);
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // N2 Freight & Loads Marketplace
        if (!Schema::hasTable('freight_loads')) {
            Schema::create('freight_loads', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('shipper_user_id')->index()->nullable();
            $table->unsignedBigInteger('origin_county_id')->index()->nullable();
            $table->unsignedBigInteger('destination_county_id')->index()->nullable();
            $table->decimal('load_value', 18, 2)->default(0);
            $table->unsignedBigInteger('weight_kg')->nullable();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // N2 bids
        if (!Schema::hasTable('freight_bids')) {
            Schema::create('freight_bids', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('freight_load_id')->index();
            $table->unsignedBigInteger('carrier_user_id')->index()->nullable();
            $table->decimal('bid_amount', 18, 2)->default(0);
            $table->decimal('commission', 18, 2)->default(0);
            $table->dateTime('won_at')->nullable();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // N3 Fuel & Fleet Inputs
        if (!Schema::hasTable('fleet_fuel_orders')) {
            Schema::create('fleet_fuel_orders', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('fleet_user_id')->index()->nullable();
            $table->string('station')->nullable();
            $table->unsignedBigInteger('litres')->default(0);
            $table->decimal('amount', 18, 2)->default(0);
            $table->decimal('commission', 18, 2)->default(0);
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('energy_products');
        Schema::dropIfExists('energy_payg_contracts');
        Schema::dropIfExists('carbon_projects');
        Schema::dropIfExists('carbon_credit_trades');
        Schema::dropIfExists('water_capex_projects');
        Schema::dropIfExists('water_project_deals');
        Schema::dropIfExists('waste_listings');
        Schema::dropIfExists('waste_transactions');
        Schema::dropIfExists('sacco_vehicles');
        Schema::dropIfExists('sacco_daily_collections');
        Schema::dropIfExists('freight_loads');
        Schema::dropIfExists('freight_bids');
        Schema::dropIfExists('fleet_fuel_orders');
    }
};
