<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hot query path indexes for county pages
        $this->indexIfNotExists('county_tourism_attractions', ['county_id', 'is_published']);
        $this->indexIfNotExists('county_hotels', ['county_id', 'is_published']);
        $this->indexIfNotExists('county_farms', ['county_id', 'is_published']);
        $this->indexIfNotExists('county_health_facilities', ['county_id', 'is_published']);
        $this->indexIfNotExists('county_institutions', ['county_id', 'is_published']);
        $this->indexIfNotExists('county_transport', ['county_id', 'is_published']);
        $this->indexIfNotExists('county_culture_sites', ['county_id', 'is_published']);
        $this->indexIfNotExists('county_products', ['county_id', 'is_published']);
        $this->indexIfNotExists('sector_entities', ['county_id', 'sector_id']);

        // Booking/order lookup indexes
        $this->indexIfNotExists('bookings', ['county_id', 'status']);
        $this->indexIfNotExists('orders', ['user_id', 'status']);
        $this->indexIfNotExists('order_items', ['order_id']);
        $this->indexIfNotExists('payment_intents', ['reference_type', 'reference_id']);
        $this->indexIfNotExists('payment_intents', ['user_id', 'status']);
        $this->indexIfNotExists('transaction_logs', ['intent_id']);
        $this->indexIfNotExists('settlement_transactions', ['batch_id']);

        // Exhibition/venue/booth
        $this->indexIfNotExists('exhibitions', ['county_id', 'status']);
        $this->indexIfNotExists('booths', ['venue_id']);
        $this->indexIfNotExists('booking_booths', ['booking_id']);
        $this->indexIfNotExists('event_sessions', ['exhibition_id']);

        // User/lookup indexes
        $this->indexIfNotExists('users', ['county_id', 'account_type']);
        $this->indexIfNotExists('model_has_roles', ['model_id', 'model_type']);

        // CMS tables
        $this->indexIfNotExists('county_sectors', ['county_id', 'is_active']);
        $this->indexIfNotExists('sector_tiles', ['county_sector_id', 'is_active']);
        $this->indexIfNotExists('tile_media', ['sector_tile_id']);

        // Sync tables
        $this->indexIfNotExists('sync_log', ['table_name', 'result']);
        $this->indexIfNotExists('sync_log', ['created_at']);
        $this->indexIfNotExists('sync_keys', ['county_slug', 'is_revoked']);

        // Ad/analytics
        $this->indexIfNotExists('ad_impressions', ['ad_id', 'created_at']);
        $this->indexIfNotExists('page_views', ['page_url', 'created_at']);
    }

    private function indexIfNotExists(string $table, array $columns): void
    {
        if (!Schema::hasTable($table)) return;

        $indexName = $table . '_' . implode('_', $columns) . '_index';
        try {
            Schema::table($table, function (Blueprint $t) use ($columns, $indexName) {
                $t->index($columns, $indexName);
            });
        } catch (\Exception $e) {
            // Index may already exist
        }
    }

    public function down(): void
    {
        // Indexes can be safely dropped, but it's not critical for rollback
    }
};
