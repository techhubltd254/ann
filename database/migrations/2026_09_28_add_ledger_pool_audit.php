<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ledger_journal')) {
            Schema::create('ledger_journal', function (Blueprint $t) {
                $t->id();
                $t->string('journal_ref')->unique();
                $t->string('memo')->nullable();
                $t->string('source')->nullable();
                $t->decimal('total_debit', 18, 4)->default(0);
                $t->decimal('total_credit', 18, 4)->default(0);
                $t->dateTime('posted_at');
                $t->unsignedBigInteger('posted_by')->nullable();
                $t->timestamps();
                $t->index(['posted_at', 'source']);
            });
        }

        if (! Schema::hasTable('ledger_entries')) {
            Schema::create('ledger_entries', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('journal_id');
                $t->string('account', 80);
                $t->decimal('debit', 18, 4)->default(0);
                $t->decimal('credit', 18, 4)->default(0);
                $t->string('ref_type')->nullable();
                $t->unsignedBigInteger('ref_id')->nullable();
                $t->timestamps();
                $t->index(['journal_id']);
                $t->index(['account']);
            });

            // FK may not be supported on TiDB — wrap safely
            try {
                DB::statement('ALTER TABLE ledger_entries ADD CONSTRAINT fk_le_journal_id FOREIGN KEY (journal_id) REFERENCES ledger_journal(id) ON DELETE CASCADE');
            } catch (\Throwable) {}
        }

        if (! Schema::hasTable('pool_periods')) {
            Schema::create('pool_periods', function (Blueprint $t) {
                $t->id();
                $t->string('period', 7);
                $t->string('scope', 30)->default('global');
                $t->string('status', 20)->default('calculating');
                $t->decimal('gross_total', 18, 4)->default(0);
                $t->decimal('holdback', 18, 4)->default(0);
                $t->decimal('equalisation', 18, 4)->default(0);
                $t->decimal('distributable', 18, 4)->default(0);
                $t->decimal('alpha', 8, 4)->default(0.7);
                $t->decimal('beta', 8, 4)->default(0.3);
                $t->dateTime('closed_at')->nullable();
                $t->timestamps();
                $t->unique(['period', 'scope']);
            });
        }

        // quality_scores and pool_distributions already exist from kit migration — skip
        if (! Schema::hasTable('quality_scores')) {
            Schema::create('quality_scores', function (Blueprint $t) {
                $t->id();
                $t->string('subject_type', 40);
                $t->unsignedBigInteger('subject_id');
                $t->decimal('score', 6, 2)->default(50);
                $t->json('components')->nullable();
                $t->dateTime('computed_at')->nullable();
                $t->timestamps();
                $t->unique(['subject_type', 'subject_id']);
            });
        }

        if (! Schema::hasTable('audit_log')) {
            Schema::create('audit_log', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('actor_id')->nullable();
                $t->string('action', 80);
                $t->string('subject_type')->nullable();
                $t->unsignedBigInteger('subject_id')->nullable();
                $t->json('meta')->nullable();
                $t->string('ip', 45)->nullable();
                $t->string('ua', 255)->nullable();
                $t->dateTime('occurred_at');
                $t->timestamps();
                $t->index(['actor_id', 'action']);
                $t->index(['subject_type', 'subject_id']);
                $t->index('occurred_at');
            });
        }

        // Add released_by to escrow if missing
        if (! Schema::hasColumn('escrow_transactions', 'released_by')) {
            Schema::table('escrow_transactions', function (Blueprint $t) {
                $t->unsignedBigInteger('released_by')->nullable()->after('released_at');
            });
        }

        // Add denial_reason to provider service tables
        foreach (['flight_inventory', 'hotel_rooms', 'airport_transfers', 'flights'] as $tbl) {
            if (Schema::hasTable($tbl) && ! Schema::hasColumn($tbl, 'denial_reason')) {
                Schema::table($tbl, function (Blueprint $t) {
                    $t->string('denial_reason', 500)->nullable();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['flight_inventory', 'hotel_rooms', 'airport_transfers', 'flights'] as $tbl) {
            if (Schema::hasTable($tbl) && Schema::hasColumn($tbl, 'denial_reason')) {
                Schema::table($tbl, fn (Blueprint $t) => $t->dropColumn('denial_reason'));
            }
        }
        if (Schema::hasColumn('escrow_transactions', 'released_by')) {
            Schema::table('escrow_transactions', fn (Blueprint $t) => $t->dropColumn('released_by'));
        }
        Schema::dropIfExists('audit_log');
        // Do NOT drop quality_scores or pool_distributions — they belong to the kit migration
        Schema::dropIfExists('pool_periods');
        Schema::dropIfExists('ledger_entries');
        Schema::dropIfExists('ledger_journal');
    }
};