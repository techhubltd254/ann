<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add scalability indexes for million-row performance.
     * Every index uses IF NOT EXISTS or hasTable guards.
     */
    public function up(): void
    {
        $db = DB::connection()->getDriverName();

        // ── pipeline_registrations ──
        $this->idx('pipeline_registrations', 'idx_pipeline_reg_code', ['code']);
        $this->idx('pipeline_registrations', 'idx_pipeline_reg_sector_status', ['sector', 'status']);

        // ── pipeline_activations ──
        if (Schema::hasTable('pipeline_activations')) {
            $this->idx('pipeline_activations', 'idx_pa_county_active', ['county_id', 'is_active']);
            $this->idx('pipeline_activations', 'idx_pa_pipeline', ['pipeline_id']);
        }

        // ── dynamic_pipelines ──
        if (Schema::hasTable('dynamic_pipelines')) {
            $this->idx('dynamic_pipelines', 'idx_dp_sector_active', ['sector', 'is_active']);
        }

        // ── escrow_transactions ──
        $this->idx('escrow_transactions', 'idx_escrow_status', ['status']);
        $this->idx('escrow_transactions', 'idx_escrow_buyer_seller', ['buyer_id', 'seller_id']);

        // ── courier_shipments ──
        $this->idx('courier_shipments', 'idx_cs_tracking', ['tracking_number']);

        // ── ledger_transactions ──
        if (Schema::hasTable('ledger_transactions')) {
            $this->idx('ledger_transactions', 'idx_lt_ref', ['reference_type', 'reference_id']);
            $this->idx('ledger_transactions', 'idx_lt_user', ['user_id', 'type']);
        }

        // ── pools ──
        if (Schema::hasTable('pools')) {
            $this->idx('pools', 'idx_pool_scope', ['scope', 'is_active']);
        }

        // ── pool_contributions ──
        if (Schema::hasTable('pool_contributions')) {
            $this->idx('pool_contributions', 'idx_pc_period', ['period_id', 'county_id']);
            $this->idx('pool_contributions', 'idx_pc_source', ['source_type', 'source_id']);
        }

        // ── pool_distributions ──
        if (Schema::hasTable('pool_distributions')) {
            $this->idx('pool_distributions', 'idx_pd_period', ['period_id', 'status']);
            $this->idx('pool_distributions', 'idx_pd_beneficiary', ['beneficiary_type', 'beneficiary_id']);
        }

        // ── quality_scores ──
        if (Schema::hasTable('quality_scores')) {
            $this->idx('quality_scores', 'idx_qs_entity', ['scorable_type', 'scorable_id']);
        }

        // ── agency_data_sources ──
        if (Schema::hasTable('agency_data_sources')) {
            $this->idx('agency_data_sources', 'idx_ads_code', ['code']);
        }

        // ── agency_data_events ──
        if (Schema::hasTable('agency_data_events')) {
            $this->idx('agency_data_events', 'idx_ade_source', ['source_id', 'status']);
            $this->idx('agency_data_events', 'idx_ade_created', ['created_at']);
        }

        // ── product search (fulltext) ──
        try {
            DB::statement('ALTER TABLE products ADD FULLTEXT INDEX idx_products_fts (name, description)');
        } catch (\Throwable $e) {
            // index may already exist
        }

        // ── orders ──
        $this->idx('orders', 'idx_orders_user', ['user_id']);
        $this->idx('orders', 'idx_orders_status', ['status']);

        // ── order_items ──
        $this->idx('order_items', 'idx_oi_order', ['order_id']);
    }

    private function idx(string $table, string $name, array $cols): void
    {
        if (!Schema::hasTable($table)) return;
        try {
            $existing = DB::select("SHOW INDEXES FROM {$table} WHERE Key_name = ?", [$name]);
            if (empty($existing)) {
                $colsSql = implode(', ', $cols);
                DB::statement("ALTER TABLE {$table} ADD INDEX {$name} ({$colsSql})");
            }
        } catch (\Throwable $e) {
            // TiDB or MySQL compat — index may already exist
        }
    }

    public function down(): void
    {
        // Index removal is not reversed — keeping indexes is safe
    }
};