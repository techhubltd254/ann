<?php

/*
|--------------------------------------------------------------------------
| Pipeline tables — cluster: agriculture
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
        // B1 Agriculture Lots
        if (!Schema::hasTable('agri_lots')) {
            Schema::create('agri_lots', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('farm_id')->index()->nullable();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->string('produce');
            $table->string('grade')->nullable();
            $table->unsignedBigInteger('quantity')->default(0);
            $table->string('unit')->default('kg');
            $table->decimal('reserve_price', 18, 2)->nullable();
            $table->date('harvest_date')->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // B1 lot sales with split payout
        if (!Schema::hasTable('agri_lot_transactions')) {
            Schema::create('agri_lot_transactions', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('agri_lot_id')->index();
            $table->unsignedBigInteger('buyer_user_id')->index()->nullable();
            $table->unsignedBigInteger('quantity')->default(0);
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->decimal('fee_charged', 18, 2)->default(0);
            $table->boolean('is_export')->default(false);
            $table->decimal('fx_spread', 5, 2)->default(0);
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // B2 Cooperative Rail
        if (!Schema::hasTable('coop_pooling_batches')) {
            Schema::create('coop_pooling_batches', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('coop_id')->index()->nullable();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->string('produce');
            $table->unsignedBigInteger('pooled_qty')->default(0);
            $table->string('target_buyer')->nullable();
            $table->decimal('payout_per_member', 18, 2)->default(0);
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // B2 member consignments
        if (!Schema::hasTable('coop_lot_consignments')) {
            Schema::create('coop_lot_consignments', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('coop_pooling_batch_id')->index();
            $table->unsignedBigInteger('member_user_id')->index()->nullable();
            $table->unsignedBigInteger('quantity')->default(0);
            $table->decimal('consignment_value', 18, 2)->default(0);
            $table->decimal('commission', 18, 2)->default(0);
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // B3 Livestock & Leather
        if (!Schema::hasTable('livestock_listings')) {
            Schema::create('livestock_listings', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->unsignedBigInteger('seller_user_id')->index()->nullable();
            $table->string('species');
            $table->unsignedBigInteger('head_count')->default(1);
            $table->boolean('vet_certified')->default(false);
            $table->decimal('reserve_price', 18, 2)->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // B3 sales
        if (!Schema::hasTable('livestock_sales')) {
            Schema::create('livestock_sales', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('livestock_listing_id')->index();
            $table->unsignedBigInteger('buyer_user_id')->index()->nullable();
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->decimal('fee_charged', 18, 2)->default(0);
            $table->dateTime('sold_at')->nullable();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // B4 Fisheries & Blue Economy
        if (!Schema::hasTable('fisheries_catches')) {
            Schema::create('fisheries_catches', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->string('vessel_or_pond')->nullable();
            $table->string('species');
            $table->unsignedBigInteger('quantity_kg')->default(0);
            $table->date('landing_date')->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // B4 sales
        if (!Schema::hasTable('fisheries_sales')) {
            Schema::create('fisheries_sales', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('fisheries_catch_id')->index();
            $table->unsignedBigInteger('buyer_user_id')->index()->nullable();
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->decimal('fee_charged', 18, 2)->default(0);
            $table->boolean('is_export')->default(false);
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // B5 Horticulture Compliance
        if (!Schema::hasTable('hort_export_permits')) {
            Schema::create('hort_export_permits', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('exporter_user_id')->index()->nullable();
            $table->string('commodity');
            $table->string('permit_no')->nullable();
            $table->string('kephis_status')->default('pending');
            $table->date('issued_at')->nullable();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // B5 per-document fees
        if (!Schema::hasTable('hort_compliance_documents')) {
            Schema::create('hort_compliance_documents', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('hort_export_permit_id')->index();
            $table->string('doc_type');
            $table->decimal('document_fee', 18, 2)->default(0);
            $table->decimal('fx_spread', 5, 2)->default(0);
            $table->dateTime('verified_at')->nullable();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // B6 Agri-Input Financing (CBK-gated)
        if (!Schema::hasTable('input_financing_advances')) {
            Schema::create('input_financing_advances', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('farmer_user_id')->index()->nullable();
            $table->string('input_type')->nullable();
            $table->decimal('advance_amount', 18, 2)->default(0);
            $table->decimal('rate_monthly', 5, 2)->default(2.5);
            $table->string('season')->nullable();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // B6 repayments
        if (!Schema::hasTable('input_financing_repayments')) {
            Schema::create('input_financing_repayments', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('input_financing_advance_id')->index();
            $table->decimal('amount', 18, 2)->default(0);
            $table->dateTime('repaid_at')->nullable();
            $table->string('recovered_from')->default('harvest');
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('agri_lots');
        Schema::dropIfExists('agri_lot_transactions');
        Schema::dropIfExists('coop_pooling_batches');
        Schema::dropIfExists('coop_lot_consignments');
        Schema::dropIfExists('livestock_listings');
        Schema::dropIfExists('livestock_sales');
        Schema::dropIfExists('fisheries_catches');
        Schema::dropIfExists('fisheries_sales');
        Schema::dropIfExists('hort_export_permits');
        Schema::dropIfExists('hort_compliance_documents');
        Schema::dropIfExists('input_financing_advances');
        Schema::dropIfExists('input_financing_repayments');
    }
};
