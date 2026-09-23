<?php

/*
|--------------------------------------------------------------------------
| Pipeline tables — cluster: government
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
        // G1 County Own-Source Revenue Rail
        if (!Schema::hasTable('county_revenue_heads')) {
            Schema::create('county_revenue_heads', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('county_id')->index();
            $table->string('head_code')->nullable();
            $table->string('name');
            $table->decimal('fee_amount', 18, 2)->nullable();
            $table->decimal('collection_fee_pct', 5, 2)->default(1.75);
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // G1 collections
        if (!Schema::hasTable('county_revenue_collections')) {
            Schema::create('county_revenue_collections', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('county_revenue_head_id')->index();
            $table->unsignedBigInteger('payer_user_id')->index()->nullable();
            $table->decimal('amount', 18, 2)->default(0);
            $table->decimal('collection_fee', 18, 2)->default(0);
            $table->string('receipt_no')->nullable();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // G2 County Procurement (IFMIS-gated)
        if (!Schema::hasTable('county_procurement_pos')) {
            Schema::create('county_procurement_pos', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('county_id')->index();
            $table->string('po_reference')->nullable();
            $table->unsignedBigInteger('supplier_user_id')->index()->nullable();
            $table->decimal('po_value', 18, 2)->default(0);
            $table->string('ifmis_ref')->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // G2 escrows
        if (!Schema::hasTable('county_procurement_escrows')) {
            Schema::create('county_procurement_escrows', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('county_procurement_po_id')->index();
            $table->decimal('escrow_amount', 18, 2)->default(0);
            $table->decimal('fee_charged', 18, 2)->default(0);
            $table->dateTime('released_at')->nullable();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // H1 National Procurement (IFMIS-gated)
        if (!Schema::hasTable('national_procurement_tenders')) {
            Schema::create('national_procurement_tenders', function (Blueprint $table) {
                $table->id();
            $table->string('tender_no')->nullable();
            $table->string('agency')->nullable();
            $table->decimal('tender_value', 18, 2)->default(0);
            $table->string('ifmis_ref')->nullable();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // H1 escrows
        if (!Schema::hasTable('national_procurement_escrows')) {
            Schema::create('national_procurement_escrows', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('national_procurement_tender_id')->index();
            $table->decimal('escrow_amount', 18, 2)->default(0);
            $table->decimal('fee_charged', 18, 2)->default(0);
            $table->dateTime('released_at')->nullable();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // G3 e-Government Facilitation
        if (!Schema::hasTable('egov_service_catalog')) {
            Schema::create('egov_service_catalog', function (Blueprint $table) {
                $table->id();
            $table->string('service_code')->nullable();
            $table->string('name');
            $table->string('agency')->nullable();
            $table->decimal('convenience_fee', 18, 2)->default(50);
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // G3 transactions
        if (!Schema::hasTable('egov_transactions')) {
            Schema::create('egov_transactions', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('egov_service_catalog_id')->index();
            $table->unsignedBigInteger('citizen_user_id')->index()->nullable();
            $table->decimal('convenience_fee', 18, 2)->default(0);
            $table->string('ecitizen_ref')->nullable();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // G4 Market Intelligence / Data Products
        if (!Schema::hasTable('data_products')) {
            Schema::create('data_products', function (Blueprint $table) {
                $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('pricing_model')->default('subscription');
            $table->decimal('price', 18, 2)->nullable();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // G4 subscriptions
        if (!Schema::hasTable('data_product_subscriptions')) {
            Schema::create('data_product_subscriptions', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('data_product_id')->index();
            $table->unsignedBigInteger('subscriber_user_id')->index()->nullable();
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->decimal('price', 18, 2)->default(0);
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('county_revenue_heads');
        Schema::dropIfExists('county_revenue_collections');
        Schema::dropIfExists('county_procurement_pos');
        Schema::dropIfExists('county_procurement_escrows');
        Schema::dropIfExists('national_procurement_tenders');
        Schema::dropIfExists('national_procurement_escrows');
        Schema::dropIfExists('egov_service_catalog');
        Schema::dropIfExists('egov_transactions');
        Schema::dropIfExists('data_products');
        Schema::dropIfExists('data_product_subscriptions');
    }
};
