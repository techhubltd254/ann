<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The commerce tables (products, orders, wishlists, …) are created by the
 * raw schema files (schema-commerce.sql) in both production TiDB and local
 * SQLite — not by a conventional migration. RefreshDatabase therefore can't
 * build them for the test suite. This migration loads the same schema for
 * SQLite test runs and is a no-op on MySQL/TiDB where the tables already
 * exist (CREATE TABLE IF NOT EXISTS is used throughout).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        $schema = database_path('schema-commerce.sql');
        if (! is_file($schema)) {
            return;
        }

        DB::unprepared(file_get_contents($schema));
    }

    public function down(): void
    {
        // Commerce tables are owned by the raw schema, not migrated in.
    }
};
