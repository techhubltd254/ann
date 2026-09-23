<?php

/*
|--------------------------------------------------------------------------
| Pipeline tables — cluster: health_education
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
        // K1 Health Facility Supply
        if (!Schema::hasTable('health_supply_catalog')) {
            Schema::create('health_supply_catalog', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('facility_id')->index()->nullable();
            $table->string('item');
            $table->decimal('unit_price', 18, 2)->nullable();
            $table->string('kemsa_ref')->nullable();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // K1 orders
        if (!Schema::hasTable('health_supply_orders')) {
            Schema::create('health_supply_orders', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('health_supply_catalog_id')->index();
            $table->unsignedBigInteger('facility_id')->index()->nullable();
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->decimal('group_buy_fee', 18, 2)->default(0);
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // K2 Health Back-office SaaS
        if (!Schema::hasTable('health_facility_subscriptions')) {
            Schema::create('health_facility_subscriptions', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('facility_id')->index()->nullable();
            $table->string('plan')->default('basic');
            $table->decimal('price_monthly', 18, 2)->default(0);
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // K3 Telehealth (BLOCKED — Digital Health Act struck down)
        if (!Schema::hasTable('telehealth_sessions')) {
            Schema::create('telehealth_sessions', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('patient_user_id')->index()->nullable();
            $table->unsignedBigInteger('provider_id')->index()->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->string('legal_status')->default('blocked');
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // L1 Skills-to-Jobs (TVET)
        if (!Schema::hasTable('skills_job_vacancies')) {
            Schema::create('skills_job_vacancies', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('employer_user_id')->index()->nullable();
            $table->string('trade');
            $table->unsignedBigInteger('headcount')->default(1);
            $table->decimal('placement_fee', 18, 2)->default(0);
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // L1 placements
        if (!Schema::hasTable('skills_placements')) {
            Schema::create('skills_placements', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('skills_job_vacancy_id')->index();
            $table->unsignedBigInteger('trainee_user_id')->index()->nullable();
            $table->decimal('placement_fee', 18, 2)->default(0);
            $table->decimal('cert_share', 18, 2)->default(0);
            $table->dateTime('placed_at')->nullable();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // L2 Labour Migration Desk
        if (!Schema::hasTable('labour_migration_applications')) {
            Schema::create('labour_migration_applications', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('worker_user_id')->index()->nullable();
            $table->string('destination_country')->nullable();
            $table->string('agency')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
                $table->timestamps();
            });
        }

        // L2 verifications
        if (!Schema::hasTable('labour_migration_verifications')) {
            Schema::create('labour_migration_verifications', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('labour_migration_application_id')->index();
            $table->decimal('verification_fee', 18, 2)->default(0);
            $table->dateTime('verified_at')->nullable();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // L3 Edu Commerce & Fee Rails
        if (!Schema::hasTable('edu_institution_fees')) {
            Schema::create('edu_institution_fees', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('institution_id')->index()->nullable();
            $table->string('fee_code')->nullable();
            $table->decimal('amount', 18, 2)->nullable();
            $table->decimal('handling_fee_pct', 5, 2)->default(0.75);
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // L3 fee payments
        if (!Schema::hasTable('edu_fee_transactions')) {
            Schema::create('edu_fee_transactions', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('edu_institution_fee_id')->index();
            $table->unsignedBigInteger('payer_user_id')->index()->nullable();
            $table->decimal('amount', 18, 2)->default(0);
            $table->decimal('handling_fee', 18, 2)->default(0);
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // L4 Attachment & IP Exchange
        if (!Schema::hasTable('attachment_opportunities')) {
            Schema::create('attachment_opportunities', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('institution_id')->index()->nullable();
            $table->unsignedBigInteger('company_user_id')->index()->nullable();
            $table->string('field')->nullable();
            $table->unsignedBigInteger('slots')->default(1);
            $table->boolean('ip_exchange')->default(false);
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // L4 placements
        if (!Schema::hasTable('attachment_placements')) {
            Schema::create('attachment_placements', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('attachment_opportunity_id')->index();
            $table->unsignedBigInteger('student_user_id')->index()->nullable();
            $table->decimal('success_fee', 18, 2)->default(0);
            $table->dateTime('placed_at')->nullable();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // L5 TVET Equipment Trade
        if (!Schema::hasTable('tvet_equipment_listings')) {
            Schema::create('tvet_equipment_listings', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('institution_id')->index()->nullable();
            $table->string('item');
            $table->unsignedBigInteger('quantity')->default(1);
            $table->decimal('unit_price', 18, 2)->nullable();
            $table->unsignedBigInteger('county_id')->index()->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // L5 orders
        if (!Schema::hasTable('tvet_equipment_orders')) {
            Schema::create('tvet_equipment_orders', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('tvet_equipment_listing_id')->index();
            $table->unsignedBigInteger('buyer_institution_id')->index()->nullable();
            $table->decimal('total_amount', 18, 2)->default(0);
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
        Schema::dropIfExists('health_supply_catalog');
        Schema::dropIfExists('health_supply_orders');
        Schema::dropIfExists('health_facility_subscriptions');
        Schema::dropIfExists('telehealth_sessions');
        Schema::dropIfExists('skills_job_vacancies');
        Schema::dropIfExists('skills_placements');
        Schema::dropIfExists('labour_migration_applications');
        Schema::dropIfExists('labour_migration_verifications');
        Schema::dropIfExists('edu_institution_fees');
        Schema::dropIfExists('edu_fee_transactions');
        Schema::dropIfExists('attachment_opportunities');
        Schema::dropIfExists('attachment_placements');
        Schema::dropIfExists('tvet_equipment_listings');
        Schema::dropIfExists('tvet_equipment_orders');
    }
};
