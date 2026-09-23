<?php

/*
|--------------------------------------------------------------------------
| Mother-Pipeline Joiner tables
|--------------------------------------------------------------------------
| Idempotent (hasTable guards). joiner_postings.posting_key is UNIQUE —
| the database-level duplicate/idempotency guard. No foreign keys (TiDB-safe).
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('joiner_runs')) {
            Schema::create('joiner_runs', function (Blueprint $table) {
                $table->id();
                $table->string('batch_id')->unique();
                $table->string('mode')->default('dry_run');
                $table->string('sector_filter')->nullable();
                $table->json('totals')->nullable();
                $table->json('variance_report')->nullable();
                $table->string('status')->default('balanced');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('joiner_postings')) {
            Schema::create('joiner_postings', function (Blueprint $table) {
                $table->id();
                $table->string('batch_id')->index();
                $table->string('posting_key')->unique();
                $table->string('pipeline_code')->index();
                $table->string('sector')->index();
                $table->string('parent')->index();
                $table->string('gl_account')->index();
                $table->decimal('debit', 18, 2)->default(0);
                $table->decimal('credit', 18, 2)->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('joiner_reconciliations')) {
            Schema::create('joiner_reconciliations', function (Blueprint $table) {
                $table->id();
                $table->string('batch_id')->index();
                $table->string('scope');
                $table->string('reference');
                $table->decimal('expected', 18, 2)->default(0);
                $table->decimal('actual', 18, 2)->default(0);
                $table->decimal('variance', 18, 2)->default(0);
                $table->boolean('balanced')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('joiner_reconciliations');
        Schema::dropIfExists('joiner_postings');
        Schema::dropIfExists('joiner_runs');
    }
};
