<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        $sql = [];
        if (!Schema::hasTable('wishlists')) {
            $sql[] = "CREATE TABLE wishlists (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL, wishlistable_type VARCHAR(255) NOT NULL, wishlistable_id BIGINT UNSIGNED NOT NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, UNIQUE KEY wishlists_unique (user_id, wishlistable_id, wishlistable_type), FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        }
        if (!Schema::hasTable('order_status_history')) {
            $sql[] = "CREATE TABLE order_status_history (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, order_id BIGINT UNSIGNED NOT NULL, status_from VARCHAR(50) NULL, status_to VARCHAR(50) NOT NULL, notes VARCHAR(255) NULL, changed_by_user_id BIGINT UNSIGNED NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE, FOREIGN KEY (changed_by_user_id) REFERENCES users(id) ON DELETE SET NULL) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        }
        if (!Schema::hasTable('return_requests')) {
            $sql[] = "CREATE TABLE return_requests (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, return_number VARCHAR(50) UNIQUE NOT NULL, order_id BIGINT UNSIGNED NOT NULL, user_id BIGINT UNSIGNED NOT NULL, order_item_id BIGINT UNSIGNED NOT NULL, reason TEXT NOT NULL, status VARCHAR(20) DEFAULT 'pending' NOT NULL, admin_notes TEXT NULL, approved_at TIMESTAMP NULL, refunded_at TIMESTAMP NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE, FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE CASCADE) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        }
        if (!Schema::hasTable('product_questions')) {
            $sql[] = "CREATE TABLE product_questions (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, product_id BIGINT UNSIGNED NOT NULL, user_id BIGINT UNSIGNED NOT NULL, question TEXT NOT NULL, answer TEXT NULL, answered_at TIMESTAMP NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        }
        if (!Schema::hasTable('recently_viewed')) {
            $sql[] = "CREATE TABLE recently_viewed (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NULL, session_id VARCHAR(255) NULL, viewable_type VARCHAR(255) NOT NULL, viewable_id BIGINT UNSIGNED NOT NULL, viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX rv_user_idx (user_id, viewable_id, viewable_type), INDEX rv_session_idx (session_id), FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        }
        if (!Schema::hasTable('auctions')) {
            $sql[] = "CREATE TABLE auctions (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, product_id BIGINT UNSIGNED NOT NULL, seller_id BIGINT UNSIGNED NOT NULL, starting_bid DECIMAL(12,2) NOT NULL, reserve_price DECIMAL(12,2) NULL, current_bid DECIMAL(12,2) DEFAULT 0 NOT NULL, increment DECIMAL(10,2) DEFAULT 100 NOT NULL, starts_at TIMESTAMP NOT NULL, ends_at TIMESTAMP NOT NULL, status VARCHAR(20) DEFAULT 'pending' NOT NULL, winner_id BIGINT UNSIGNED NULL, winning_bid DECIMAL(12,2) NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE, FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE, FOREIGN KEY (winner_id) REFERENCES users(id) ON DELETE SET NULL) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            $sql[] = "CREATE TABLE auction_bids (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, auction_id BIGINT UNSIGNED NOT NULL, user_id BIGINT UNSIGNED NOT NULL, amount DECIMAL(12,2) NOT NULL, is_auto TINYINT(1) DEFAULT 0 NOT NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, INDEX auction_bids_auction_amount (auction_id, amount), FOREIGN KEY (auction_id) REFERENCES auctions(id) ON DELETE CASCADE, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        }
        if (!Schema::hasTable('rfqs')) {
            $sql[] = "CREATE TABLE rfqs (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, rfq_number VARCHAR(50) UNIQUE NOT NULL, buyer_id BIGINT UNSIGNED NOT NULL, product_name VARCHAR(255) NOT NULL, quantity INT NOT NULL, specifications TEXT NULL, budget_min DECIMAL(12,2) NULL, budget_max DECIMAL(12,2) NULL, deadline DATE NULL, status VARCHAR(20) DEFAULT 'open' NOT NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            $sql[] = "CREATE TABLE rfq_quotes (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, rfq_id BIGINT UNSIGNED NOT NULL, seller_id BIGINT UNSIGNED NOT NULL, price DECIMAL(12,2) NOT NULL, notes TEXT NULL, status VARCHAR(20) DEFAULT 'pending' NOT NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, FOREIGN KEY (rfq_id) REFERENCES rfqs(id) ON DELETE CASCADE, FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        }
        if (!Schema::hasTable('flash_sales')) {
            $sql[] = "CREATE TABLE flash_sales (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255) NOT NULL, description TEXT NULL, discount_percent DECIMAL(5,2) NOT NULL, starts_at TIMESTAMP NOT NULL, ends_at TIMESTAMP NOT NULL, is_active TINYINT(1) DEFAULT 1 NOT NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            $sql[] = "CREATE TABLE flash_sale_products (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, flash_sale_id BIGINT UNSIGNED NOT NULL, product_id BIGINT UNSIGNED NOT NULL, max_qty INT DEFAULT 0 NOT NULL, sold_qty INT DEFAULT 0 NOT NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, FOREIGN KEY (flash_sale_id) REFERENCES flash_sales(id) ON DELETE CASCADE, FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        }
        if (!Schema::hasTable('gift_cards')) {
            $sql[] = "CREATE TABLE gift_cards (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, code VARCHAR(20) UNIQUE NOT NULL, initial_balance DECIMAL(12,2) NOT NULL, balance DECIMAL(12,2) NOT NULL, issued_by_user_id BIGINT UNSIGNED NOT NULL, redeemed_by_user_id BIGINT UNSIGNED NULL, expires_at TIMESTAMP NULL, redeemed_at TIMESTAMP NULL, is_active TINYINT(1) DEFAULT 1 NOT NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, FOREIGN KEY (issued_by_user_id) REFERENCES users(id) ON DELETE CASCADE, FOREIGN KEY (redeemed_by_user_id) REFERENCES users(id) ON DELETE SET NULL) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        }

        // Add columns to product_variants
        try { DB::statement("ALTER TABLE product_variants ADD COLUMN moq INT DEFAULT 1 NOT NULL AFTER stock"); } catch (\Exception $e) {}
        try { DB::statement("ALTER TABLE product_variants ADD COLUMN tier_prices JSON NULL AFTER moq"); } catch (\Exception $e) {}

        foreach ($sql as $q) {
            try { DB::statement($q); } catch (\Exception $e) { /* table may exist from prior run */ }
        }
    }

    public function down(): void {
        Schema::dropIfExists('gift_cards');
        Schema::dropIfExists('flash_sale_products');
        Schema::dropIfExists('flash_sales');
        Schema::dropIfExists('rfq_quotes');
        Schema::dropIfExists('rfqs');
        Schema::dropIfExists('auction_bids');
        Schema::dropIfExists('auctions');
        Schema::dropIfExists('recently_viewed');
        Schema::dropIfExists('product_questions');
        Schema::dropIfExists('return_requests');
        Schema::dropIfExists('order_status_history');
        Schema::dropIfExists('wishlists');
    }
};