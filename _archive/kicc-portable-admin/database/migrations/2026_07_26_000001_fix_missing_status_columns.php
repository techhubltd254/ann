<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addColumnIfNotExists('ad_campaigns', 'is_active', function (Blueprint $t) {
            $t->boolean('is_active')->default(false);
        });
        $this->addColumnIfNotExists('courier_partners', 'is_active', function (Blueprint $t) {
            $t->boolean('is_active')->default(false);
        });
        $this->addColumnIfNotExists('shipping_zones', 'is_active', function (Blueprint $t) {
            $t->boolean('is_active')->default(false);
        });
        $this->addColumnIfNotExists('content_pages', 'is_published', function (Blueprint $t) {
            $t->boolean('is_published')->default(false);
        });
    }

    private function addColumnIfNotExists(string $table, string $column, callable $definition): void
    {
        try {
            Schema::table($table, function (Blueprint $t) use ($column, $definition) {
                if (!Schema::hasColumn($table, $column)) {
                    $definition($t);
                }
            });
        } catch (\Throwable $e) {
            // Table might not exist either — skip gracefully
        }
    }

    public function down(): void
    {
        // No automated down — too risky with various table states
    }
};