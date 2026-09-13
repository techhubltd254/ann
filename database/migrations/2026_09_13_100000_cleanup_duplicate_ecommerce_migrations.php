<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        foreach ([
            '2026_08_15_220000_create_wishlists_table',
            '2026_08_15_221000_create_order_status_history_table',
            '2026_08_15_222000_create_return_requests_table',
            '2026_08_15_223000_create_product_questions_table',
            '2026_08_15_224000_create_recently_viewed_table',
            '2026_08_15_225000_create_auctions_table',
            '2026_08_15_226000_create_b2b_features_table',
            '2026_08_15_227000_create_flash_sales_table',
            '2026_08_15_228000_create_gift_cards_table',
        ] as $migration) {
            try {
                $class = require database_path("migrations/{$migration}.php");
                $class->down();
            } catch (\Throwable $e) {
                Log::info("migrate:cleanup — {$migration} down skipped: {$e->getMessage()}");
            }
        }
        Log::info('Duplicate ecommerce migrations cleaned up');
    }

    public function down(): void
    {
        // No rollback — the per-table migrations still exist if needed
    }
};