<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // IDEMPOTENT_GUARD: skip when table missing / changes already applied
        if (!Schema::hasTable('orders')) {
            return;
        }
        try {
        $missing = fn ($table, $index) => Schema::hasTable($table)
            && ! collect(Schema::getIndexes($table))->contains(fn ($i) => $i['name'] === $index);
        $hasCols = function (string $table, array $cols): bool {
            if (! Schema::hasTable($table)) {
                return false;
            }
            $existing = array_column(Schema::getColumns($table), 'name');
            return count(array_intersect($cols, $existing)) === count($cols);
        };

        // Orders: composite for dashboard + status lookups
        if ($missing('orders', 'idx_orders_user_payment_date')) {
            Schema::table('orders', fn (Blueprint $t) => $t->index(['user_id', 'payment_status', 'placed_at'], 'idx_orders_user_payment_date'));
        }
        if ($missing('orders', 'idx_orders_user_fulfillment')) {
            Schema::table('orders', fn (Blueprint $t) => $t->index(['user_id', 'fulfillment_status'], 'idx_orders_user_fulfillment'));
        }
        if ($missing('orders', 'idx_orders_payment_fulfillment')) {
            Schema::table('orders', fn (Blueprint $t) => $t->index(['payment_status', 'fulfillment_status'], 'idx_orders_payment_fulfillment'));
        }

        // Products: vendor dashboard + category browsing
        if ($missing('products', 'idx_products_user_status_date')) {
            Schema::table('products', fn (Blueprint $t) => $t->index(['user_id', 'status', 'created_at'], 'idx_products_user_status_date'));
        }
        if ($missing('products', 'idx_products_county_cat_status')) {
            Schema::table('products', fn (Blueprint $t) => $t->index(['county_id', 'category_id', 'status'], 'idx_products_county_cat_status'));
        }
        if ($missing('products', 'idx_products_slug_status')) {
            Schema::table('products', fn (Blueprint $t) => $t->index(['slug', 'status'], 'idx_products_slug_status'));
        }

        // Bookings: venue + date lookups
        if ($missing('bookings', 'idx_bookings_venue_date_status') && $hasCols('bookings', ['venue_id', 'start_date', 'status'])) {
            Schema::table('bookings', fn (Blueprint $t) => $t->index(['venue_id', 'start_date', 'status'], 'idx_bookings_venue_date_status'));
        }
        if ($missing('bookings', 'idx_bookings_user_status') && $hasCols('bookings', ['user_id', 'status'])) {
            Schema::table('bookings', fn (Blueprint $t) => $t->index(['user_id', 'status'], 'idx_bookings_user_status'));
        }

        // Escrow: status flows + user lookups
        if ($missing('escrow_transactions', 'idx_escrow_buyer_status')) {
            Schema::table('escrow_transactions', fn (Blueprint $t) => $t->index(['buyer_id', 'status'], 'idx_escrow_buyer_status'));
        }
        if ($missing('escrow_transactions', 'idx_escrow_seller_status')) {
            Schema::table('escrow_transactions', fn (Blueprint $t) => $t->index(['seller_id', 'status'], 'idx_escrow_seller_status'));
        }
        if ($missing('escrow_transactions', 'idx_escrow_status_date')) {
            Schema::table('escrow_transactions', fn (Blueprint $t) => $t->index(['status', 'created_at'], 'idx_escrow_status_date'));
        }

        // Disputes: escrow + status lookups
        if ($missing('dispute_cases', 'idx_disputes_escrow_status')) {
            Schema::table('dispute_cases', fn (Blueprint $t) => $t->index(['escrow_transaction_id', 'status'], 'idx_disputes_escrow_status'));
        }
        if ($missing('dispute_cases', 'idx_disputes_raised_status')) {
            Schema::table('dispute_cases', fn (Blueprint $t) => $t->index(['raised_by', 'status'], 'idx_disputes_raised_status'));
        }

        // Courier: tracking + status
        if ($missing('courier_shipments', 'idx_courier_escrow_status')) {
            Schema::table('courier_shipments', fn (Blueprint $t) => $t->index(['escrow_transaction_id', 'status'], 'idx_courier_escrow_status'));
        }
        if ($missing('courier_shipments', 'idx_courier_tracking_status')) {
            Schema::table('courier_shipments', fn (Blueprint $t) => $t->index(['tracking_number', 'status'], 'idx_courier_tracking_status'));
        }

        // Courier tracking events: shipment + timeline
        if ($missing('courier_tracking_events', 'idx_tracking_shipment_date')) {
            Schema::table('courier_tracking_events', fn (Blueprint $t) => $t->index(['courier_shipment_id', 'occurred_at'], 'idx_tracking_shipment_date'));
        }

        // Payments: status + reference lookups
        if ($missing('payment_intents', 'idx_payment_user_status')) {
            Schema::table('payment_intents', fn (Blueprint $t) => $t->index(['user_id', 'status'], 'idx_payment_user_status'));
        }
        if ($missing('payment_intents', 'idx_payment_reference')) {
            Schema::table('payment_intents', fn (Blueprint $t) => $t->index(['reference_type', 'reference_id'], 'idx_payment_reference'));
        }
        if ($missing('payment_intents', 'idx_payment_status_date')) {
            Schema::table('payment_intents', fn (Blueprint $t) => $t->index(['status', 'created_at'], 'idx_payment_status_date'));
        }

        // Sector entities: county + sector lookups (frequently queried)
        if ($missing('sector_entities', 'idx_sector_entities_county_sector_pub')) {
            Schema::table('sector_entities', fn (Blueprint $t) => $t->index(['county_id', 'sector_id', 'is_published'], 'idx_sector_entities_county_sector_pub'));
        }
        if ($missing('sector_entities', 'idx_sector_type_capture')) {
            Schema::table('sector_entities', fn (Blueprint $t) => $t->index(['sector_type', 'is_published', 'capture_status'], 'idx_sector_type_capture'));
        }

        // County sector: pivot lookups
        if ($missing('county_sector', 'idx_county_sector_county_sector')) {
            Schema::table('county_sector', fn (Blueprint $t) => $t->index(['county_id', 'sector_id'], 'idx_county_sector_county_sector'));
        }

        // Order items: vendor dashboard lookups
        if ($missing('order_items', 'idx_order_items_supplier_order')) {
            Schema::table('order_items', fn (Blueprint $t) => $t->index(['supplier_id', 'order_id'], 'idx_order_items_supplier_order'));
        }
        if ($missing('order_items', 'idx_order_items_product_date')) {
            Schema::table('order_items', fn (Blueprint $t) => $t->index(['product_id', 'created_at'], 'idx_order_items_product_date'));
        }
    
        } catch (\Throwable $e) {
            // already applied — ignore
        }
}

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $t) {
            $t->dropIndex('idx_orders_user_payment_date');
            $t->dropIndex('idx_orders_user_fulfillment');
            $t->dropIndex('idx_orders_payment_fulfillment');
        });
        Schema::table('products', function (Blueprint $t) {
            $t->dropIndex('idx_products_user_status_date');
            $t->dropIndex('idx_products_county_cat_status');
            $t->dropIndex('idx_products_slug_status');
        });
        Schema::table('bookings', function (Blueprint $t) {
            $t->dropIndex('idx_bookings_venue_date_status');
            $t->dropIndex('idx_bookings_user_status');
        });
        Schema::table('escrow_transactions', function (Blueprint $t) {
            $t->dropIndex('idx_escrow_buyer_status');
            $t->dropIndex('idx_escrow_seller_status');
            $t->dropIndex('idx_escrow_status_date');
        });
        Schema::table('dispute_cases', function (Blueprint $t) {
            $t->dropIndex('idx_disputes_escrow_status');
            $t->dropIndex('idx_disputes_raised_status');
        });
        Schema::table('courier_shipments', function (Blueprint $t) {
            $t->dropIndex('idx_courier_escrow_status');
            $t->dropIndex('idx_courier_tracking_status');
        });
        Schema::table('courier_tracking_events', function (Blueprint $t) {
            $t->dropIndex('idx_tracking_shipment_date');
        });
        Schema::table('payment_intents', function (Blueprint $t) {
            $t->dropIndex('idx_payment_user_status');
            $t->dropIndex('idx_payment_reference');
            $t->dropIndex('idx_payment_status_date');
        });
        Schema::table('sector_entities', function (Blueprint $t) {
            $t->dropIndex('idx_sector_entities_county_sector_pub');
            $t->dropIndex('idx_sector_type_capture');
        });
        Schema::table('county_sector', function (Blueprint $t) {
            $t->dropIndex('idx_county_sector_county_sector');
        });
        Schema::table('order_items', function (Blueprint $t) {
            $t->dropIndex('idx_order_items_supplier_order');
            $t->dropIndex('idx_order_items_product_date');
        });
    }
};
