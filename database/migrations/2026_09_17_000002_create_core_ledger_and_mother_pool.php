<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Core: GL, ledger, pipeline registry.
 * Pool tables (pools, pool_contributions, pool_distributions, quality_scores,
 * county_classifications) are owned by migration 2026_09_20_000001 — skipped here.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('gl_accounts')) {
            Schema::create('gl_accounts', function (Blueprint $t) {
                $t->id();
                $t->string('code')->unique();
                $t->string('name');
                $t->string('type')->default('asset');
                $t->string('currency')->default('KES');
                $t->unsignedBigInteger('parent_id')->index()->nullable();
                $t->boolean('is_active')->default(true);
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('gl_journal_entries')) {
            Schema::create('gl_journal_entries', function (Blueprint $t) {
                $t->id();
                $t->string('entry_no')->unique();
                $t->string('reference_type')->nullable();
                $t->unsignedBigInteger('reference_id')->index()->nullable();
                $t->string('description')->nullable();
                $t->dateTime('posted_at')->nullable();
                $t->unsignedBigInteger('reversal_of')->nullable();
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('gl_journal_lines')) {
            Schema::create('gl_journal_lines', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('journal_entry_id')->index();
                $t->unsignedBigInteger('gl_account_id')->index();
                $t->decimal('debit', 18, 2)->default(0);
                $t->decimal('credit', 18, 2)->default(0);
                $t->string('memo')->nullable();
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('ledger_transactions')) {
            Schema::create('ledger_transactions', function (Blueprint $t) {
                $t->id();
                $t->string('uuid')->unique();
                $t->string('pipeline_code')->index();
                $t->unsignedBigInteger('sector_id')->index()->nullable();
                $t->unsignedBigInteger('user_id')->index()->nullable();
                $t->string('type')->default('hold');
                $t->decimal('amount', 18, 2);
                $t->string('currency')->default('KES');
                $t->string('status')->default('pending');
                $t->string('reference_type')->nullable();
                $t->unsignedBigInteger('reference_id')->index()->nullable();
                $t->unsignedBigInteger('county_id')->index()->nullable();
                $t->json('meta')->nullable();
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('ledger_holds')) {
            Schema::create('ledger_holds', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('ledger_transaction_id')->index();
                $t->decimal('amount', 18, 2);
                $t->dateTime('held_at')->nullable();
                $t->dateTime('expires_at')->nullable();
                $t->dateTime('released_at')->nullable();
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('ledger_splits')) {
            Schema::create('ledger_splits', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('ledger_transaction_id')->index();
                $t->string('payee_type');
                $t->unsignedBigInteger('payee_id')->index();
                $t->string('split_code');
                $t->decimal('amount', 18, 2);
                $t->string('status')->default('pending');
                $t->dateTime('paid_at')->nullable();
                $t->json('meta')->nullable();
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('pipeline_registrations')) {
            Schema::create('pipeline_registrations', function (Blueprint $t) {
                $t->id();
                $t->string('code', 20)->unique();
                $t->string('parent', 10)->index();
                $t->string('sector', 30)->index();
                $t->string('slug');
                $t->string('phase', 1)->default('1');
                $t->string('status', 30)->default('absent');
                $t->json('economics');
                $t->json('regulators')->nullable();
                $t->json('tables')->nullable();
                $t->json('kill_criteria')->nullable();
                $t->boolean('earning_locked')->default(false);
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('pipeline_events')) {
            Schema::create('pipeline_events', function (Blueprint $t) {
                $t->id();
                $t->string('pipeline_code')->index();
                $t->string('event_type', 50)->index();
                $t->json('payload')->nullable();
                $t->timestamps();
                $t->index(['pipeline_code', 'event_type', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pipeline_events');
        Schema::dropIfExists('pipeline_registrations');
        Schema::dropIfExists('ledger_splits');
        Schema::dropIfExists('ledger_holds');
        Schema::dropIfExists('ledger_transactions');
        Schema::dropIfExists('gl_journal_lines');
        Schema::dropIfExists('gl_journal_entries');
        Schema::dropIfExists('gl_accounts');
    }
};
