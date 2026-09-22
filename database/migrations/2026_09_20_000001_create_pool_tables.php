<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mother Pool — all proceeds from every sector/county/pipeline accrue into
 * one pool; monthly payouts flow back weighted by contribution × quality.
 *
 * Tables: pools, pool_contributions, quality_scores, pool_distributions,
 * sponsor_referrals, plus classification columns on counties.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Pools ──
        if (!Schema::hasTable('pools')) {
            Schema::create('pools', function (Blueprint $t) {
                $t->id();
                $t->string('name', 100);
                $t->string('scope', 30)->default('global');
                $t->unsignedBigInteger('scope_id')->nullable();
                $t->decimal('balance', 14, 2)->default(0);
                $t->decimal('holdback_pct', 5, 2)->default(10.00);
                $t->decimal('equalisation_pct', 5, 2)->default(0.50);
                $t->boolean('is_active')->default(true);
                $t->timestamps();
            });
        }

        // ── 2. Pool Contributions ──
        if (!Schema::hasTable('pool_contributions')) {
            Schema::create('pool_contributions', function (Blueprint $t) {
                $t->id();
                $t->foreignId('pool_id')->constrained()->cascadeOnDelete();
                $t->string('source_type', 50);
                $t->unsignedBigInteger('source_id');
                $t->foreignId('county_id')->nullable()->constrained()->nullOnDelete();
                $t->foreignId('sector_id')->nullable()->constrained()->nullOnDelete();
                $t->nullableMorphs('entity');
                $t->decimal('gross_amount', 12, 2);
                $t->decimal('platform_fee', 12, 2);
                $t->decimal('pool_share', 12, 2);
                $t->decimal('quality_score', 5, 2)->nullable();
                $t->string('period_id', 7);
                $t->timestamps();
                $t->index(['pool_id', 'period_id']);
                $t->index(['county_id', 'sector_id', 'period_id']);
            });
        }

        // ── 3. Quality Scores ──
        if (!Schema::hasTable('quality_scores')) {
            Schema::create('quality_scores', function (Blueprint $t) {
                $t->id();
                $t->morphs('scoreable');
                $t->decimal('score', 5, 2);
                $t->json('components')->nullable();
                $t->string('algorithm_version', 20);
                $t->timestamp('computed_at');
                $t->timestamps();
            });
        }

        // ── 4. Pool Distributions ──
        if (!Schema::hasTable('pool_distributions')) {
            Schema::create('pool_distributions', function (Blueprint $t) {
                $t->id();
                $t->foreignId('pool_id')->constrained()->cascadeOnDelete();
                $t->string('period_id', 7);
                $t->string('beneficiary_type', 50);
                $t->unsignedBigInteger('beneficiary_id')->nullable();
                $t->decimal('contribution_weight', 8, 6);
                $t->decimal('quality_weight', 8, 6);
                $t->decimal('final_weight', 8, 6);
                $t->decimal('amount', 12, 2);
                $t->json('breakdown')->nullable();
                $t->string('status', 20)->default('pending');
                $t->timestamp('settled_at')->nullable();
                $t->timestamps();
            });
        }

        // ── 5. Sponsor Referrals ──
        if (!Schema::hasTable('sponsor_referrals')) {
            Schema::create('sponsor_referrals', function (Blueprint $t) {
                $t->id();
                $t->foreignId('sponsor_id')->constrained('users')->cascadeOnDelete();
                $t->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
                $t->foreignId('pool_contribution_id')->nullable()->constrained()->nullOnDelete();
                $t->decimal('amount', 12, 2);
                $t->string('status', 20)->default('pending');
                $t->timestamps();
            });
        }

        // ── 6. County classification columns ──
        Schema::table('counties', function (Blueprint $t) {
            if (!Schema::hasColumn('counties', 'classification_rps')) {
                $t->decimal('classification_rps', 6, 4)->nullable()->after('is_active');
            }
            if (!Schema::hasColumn('counties', 'classification_fns')) {
                $t->decimal('classification_fns', 6, 4)->nullable()->after('classification_rps');
            }
            if (!Schema::hasColumn('counties', 'classification_quadrant')) {
                $t->string('classification_quadrant', 30)->nullable()->after('classification_fns');
            }
        });

        // ── 7. Idempotency store (used by settlement + escrow chain) ──
        if (!Schema::hasTable('idempotency_keys')) {
            Schema::create('idempotency_keys', function (Blueprint $t) {
                $t->id();
                $t->string('key', 100)->unique();
                $t->timestamp('expires_at')->index();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');
        Schema::dropIfExists('sponsor_referrals');
        Schema::dropIfExists('pool_distributions');
        Schema::dropIfExists('quality_scores');
        Schema::dropIfExists('pool_contributions');
        Schema::dropIfExists('pools');
        Schema::table('counties', function (Blueprint $t) {
            $t->dropColumn(['classification_rps', 'classification_fns', 'classification_quadrant']);
        });
    }
};