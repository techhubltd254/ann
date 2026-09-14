<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // IDEMPOTENT_GUARD: skip when table missing / changes already applied
        if (!Schema::hasTable('model_has_permissions')) {
            return;
        }
        try {
        $columnNames = config('permission.column_names');
        $morphKey = $columnNames['model_morph_key'] ?? 'model_id';

        // Secondary index on (model_id, model_type) — enables efficient
        // polymorphic lookups without requiring permission_id in the WHERE.
        Schema::table('model_has_permissions', function (Blueprint $table) use ($morphKey) {
            $indexName = 'model_has_permissions_model_id_model_type_index';
            $table->index([$morphKey, 'model_type'], $indexName);
        });

        Schema::table('model_has_roles', function (Blueprint $table) use ($morphKey) {
            $indexName = 'model_has_roles_model_id_model_type_index';
            $table->index([$morphKey, 'model_type'], $indexName);
        });

        // role_has_permissions has a composite PK (permission_id, role_id).
        // Queries filtering by role_id alone cannot use that PK — add a
        // dedicated index on role_id.
        Schema::table('role_has_permissions', function (Blueprint $table) {
            $table->index('role_id', 'role_has_permissions_role_id_index');
        });
    
        } catch (\Throwable $e) {
            // already applied — ignore
        }
}

    public function down(): void
    {
        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->dropIndex('model_has_permissions_model_id_model_type_index');
        });
        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->dropIndex('model_has_roles_model_id_model_type_index');
        });
        Schema::table('role_has_permissions', function (Blueprint $table) {
            $table->dropIndex('role_has_permissions_role_id_index');
        });
    }
};