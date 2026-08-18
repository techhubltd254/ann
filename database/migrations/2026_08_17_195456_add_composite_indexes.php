<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Orders: composite for dashboard + status lookups
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $t) {
                $t->index(['user_id', 'payment_status', 'placed_at'], 'idx_orders_user_payment_date');
                $t->index(['user_id', 'fulfillment_status'], 'idx_orders_user_fulfillment');
                $t->index(['payment_status', 'fulfillment_status'], 'idx_orders_payment_fulfillment');
            });
        }

        // Products: vendor dashboard + category browsing
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $t) {
                $t->index(['user_id', 'status', 'created_at'], 'idx_products_user_status_date');
                $t->index(['county_id', 'category_id', 'status'], 'idx_products_county_cat_status');
                $t->index(['slug', 'status'], 'idx_products_slug_status');
            });
        }

        // Bookings: venue + date lookups
        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $t) {
                $t->index(['venue_id', 'start_date', 'status'], 'idx_bookings_venue_date_status');
                $t->index(['user_id', 'status'], 'idx_bookings_user_status');
            });
        }

        // Escrow: status flows + user lookups
        if (Schema::hasTable('escrow_transactions')) {
            Schema::table('escrow_transactions', function (Blueprint $t) {
                $t->index(['buyer_id', 'status'], 'idx_escrow_buyer_status');
                $t->index(['seller_id', 'status'], 'idx_escrow_seller_status');
                $t->index(['status', 'created_at'], 'idx_escrow_status_date');
            });
        }

        // Disputes: escrow + status lookups
        if (Schema::hasTable('dispute_cases')) {
            Schema::table('dispute_cases', function (Blueprint $t) {
                $t->index(['escrow_transaction_id', 'status'], 'idx_disputes_escrow_status');
                $t->index(['raised_by', 'status'], 'idx_disputes_raised_status');
            });
        }

        // Courier: tracking + status
        if (Schema::hasTable('courier_shipments')) {
            Schema::table('courier_shipments', function (Blueprint $t) {
                $t->index(['escrow_transaction_id', 'status'], 'idx_courier_escrow_status');
                $t->index(['tracking_number', 'status'], 'idx_courier_tracking_status');
            });
        }

        // Courier tracking events: shipment + timeline
        if (Schema::hasTable('courier_tracking_events')) {
            Schema::table('courier_tracking_events', function (Blueprint $t) {
                $t->index(['courier_shipment_id', 'occurred_at'], 'idx_tracking_shipment_date');
            });
        }

        // Payments: status + reference lookups
        if (Schema::hasTable('payment_intents')) {
            Schema::table('payment_intents', function (Blueprint $t) {
                $t->index(['user_id', 'status'], 'idx_payment_user_status');
                $t->index(['reference_type', 'reference_id'], 'idx_payment_reference');
                $t->index(['status', 'created_at'], 'idx_payment_status_date');
            });
        }

        // Sector entities: county + sector lookups (frequently queried)
        if (Schema::hasTable('sector_entities')) {
            Schema::table('sector_entities', function (Blueprint $t) {
                $t->index(['county_id', 'sector_id', 'is_published'], 'idx_sector_entities_county_sector_pub');
                $t->index(['sector_type', 'is_published', 'capture_status'], 'idx_sector_type_capture');
            });
        }

        // County sector: pivot lookups
        if (Schema::hasTable('county_sector')) {
            Schema::table('county_sector', function (Blueprint $t) {
                $t->index(['county_id', 'sector_id'], 'idx_county_sector_county_sector');
            });
        }

        // Order items: vendor dashboard lookups
        if (Schema::hasTable('order_items')) {
            Schema::table('order_items', function (Blueprint $t) {
                $t->index(['supplier_id', 'order_id'], 'idx_order_items_supplier_order');
                $t->index(['product_id', 'created_at'], 'idx_order_items_product_date');
            });
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
