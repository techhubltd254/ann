<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Add a public UUID column to users.
 *
 * The integer `id` stays the internal PK (45 FK columns across ~40 tables,
 * Sanctum tokens, sessions, Spatie pivots and the Kotlin engine all depend on
 * it). `uuid` is exposed via the API so client-facing identifiers are
 * non-enumerable and stable across merges. Backfills existing rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'uuid')) {
            Schema::table('users', function (Blueprint $t) {
                $t->uuid('uuid')->nullable()->after('id');
            });

            // Backfill existing users with random UUIDs (MySQL doesn't support
            // DEFAULT uuid() on TiDB serverless for column defaults this way).
            DB::table('users')->select('id')->orderBy('id')->chunkById(500, function ($users) {
                foreach ($users as $user) {
                    DB::table('users')->where('id', $user->id)->update(['uuid' => (string) Str::uuid()]);
                }
            });

            Schema::table('users', function (Blueprint $t) {
                $t->uuid('uuid')->nullable(false)->change();
                $t->unique('uuid', 'users_uuid_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'uuid')) {
            Schema::table('users', function (Blueprint $t) {
                $t->dropUnique('users_uuid_unique');
                $t->dropColumn('uuid');
            });
        }
    }
};