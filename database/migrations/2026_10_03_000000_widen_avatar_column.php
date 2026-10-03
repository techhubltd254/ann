<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'avatar')) {
            DB::statement('ALTER TABLE users MODIFY avatar TEXT NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'avatar')) {
            DB::statement('ALTER TABLE users MODIFY avatar VARCHAR(255) NULL');
        }
    }
};
