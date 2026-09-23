<?php

/*
|--------------------------------------------------------------------------
| Pipeline tables — cluster: financing_risk
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
        // F1 Financing & Treasury (CBK-gated)
        if (!Schema::hasTable('financing_facilities')) {
            Schema::create('financing_facilities', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('borrower_user_id')->index()->nullable();
            $table->decimal('limit_amount', 18, 2)->default(0);
            $table->decimal('rate_monthly', 5, 2)->default(2.5);
            $table->string('cbk_licence_ref')->nullable();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // F1 disbursements
        if (!Schema::hasTable('financing_disbursements')) {
            Schema::create('financing_disbursements', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('financing_facility_id')->index();
            $table->decimal('amount', 18, 2)->default(0);
            $table->decimal('interest', 18, 2)->default(0);
            $table->date('due_date')->nullable();
            $table->dateTime('disbursed_at')->nullable();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // F2 Supplier / Invoice Financing (CBK-gated)
        if (!Schema::hasTable('invoice_financing_requests')) {
            Schema::create('invoice_financing_requests', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('supplier_user_id')->index()->nullable();
            $table->string('po_reference')->nullable();
            $table->decimal('invoice_amount', 18, 2)->default(0);
            $table->boolean('verified_po')->default(false);
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // F2 deals
        if (!Schema::hasTable('invoice_financing_deals')) {
            Schema::create('invoice_financing_deals', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('invoice_financing_request_id')->index();
            $table->decimal('advance_amount', 18, 2)->default(0);
            $table->decimal('rate_monthly', 5, 2)->default(2.5);
            $table->dateTime('settled_at')->nullable();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // F3 Input Financing (CBK-gated)
        if (!Schema::hasTable('input_advance_requests')) {
            Schema::create('input_advance_requests', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('farmer_user_id')->index()->nullable();
            $table->string('input_type')->nullable();
            $table->decimal('advance_amount', 18, 2)->default(0);
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // F3 deals
        if (!Schema::hasTable('input_advance_deals')) {
            Schema::create('input_advance_deals', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('input_advance_request_id')->index();
            $table->decimal('advance_amount', 18, 2)->default(0);
            $table->date('recovery_due')->nullable();
            $table->dateTime('recovered_at')->nullable();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // F4 Embedded Insurance (IRA-gated)
        if (!Schema::hasTable('embedded_insurance_policies')) {
            Schema::create('embedded_insurance_policies', function (Blueprint $table) {
                $table->id();
            $table->string('policy_ref')->nullable();
            $table->unsignedBigInteger('user_id')->index()->nullable();
            $table->string('product')->nullable();
            $table->decimal('premium', 18, 2)->default(0);
            $table->decimal('commission', 18, 2)->default(0);
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // F4/P1 KYB-as-a-Service
        if (!Schema::hasTable('kyb_checks')) {
            Schema::create('kyb_checks', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('business_user_id')->index()->nullable();
            $table->string('check_type')->default('kyb');
            $table->string('result')->nullable();
            $table->string('api_client')->nullable();
            $table->decimal('fee_charged', 18, 2)->default(0);
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // F5 Escrow Float (KES 0 until CBK confirms)
        if (!Schema::hasTable('escrow_float_accounts')) {
            Schema::create('escrow_float_accounts', function (Blueprint $table) {
                $table->id();
            $table->string('pool_name');
            $table->decimal('balance', 18, 2)->default(0);
            $table->string('cbk_clarity_status')->default('pending');
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // F5 yield ledger
        if (!Schema::hasTable('escrow_float_yields')) {
            Schema::create('escrow_float_yields', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('escrow_float_account_id')->index();
            $table->decimal('yield_amount', 18, 2)->default(0);
            $table->date('accrued_at')->nullable();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('financing_facilities');
        Schema::dropIfExists('financing_disbursements');
        Schema::dropIfExists('invoice_financing_requests');
        Schema::dropIfExists('invoice_financing_deals');
        Schema::dropIfExists('input_advance_requests');
        Schema::dropIfExists('input_advance_deals');
        Schema::dropIfExists('embedded_insurance_policies');
        Schema::dropIfExists('kyb_checks');
        Schema::dropIfExists('escrow_float_accounts');
        Schema::dropIfExists('escrow_float_yields');
    }
};
