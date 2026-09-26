<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Bus SQL tables — uses raw SQL CREATE TABLE IF NOT EXISTS
 * to avoid information_schema restrictions on TiDB Cloud.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TABLE IF NOT EXISTS bus_events (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            topic VARCHAR(100) NOT NULL,
            payload JSON,
            metadata JSON,
            idempotency_key VARCHAR(255),
            correlation_id VARCHAR(100),
            causation_id VARCHAR(100),
            hop INT DEFAULT 0,
            published_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_be_topic (topic),
            INDEX idx_be_correlation (correlation_id),
            INDEX idx_be_published (published_at)
        )');

        DB::statement('CREATE TABLE IF NOT EXISTS consumer_offsets (
            consumer_group VARCHAR(50) PRIMARY KEY,
            ack_offset BIGINT NOT NULL DEFAULT 0,
            processed_count BIGINT DEFAULT 0,
            dlq_count BIGINT DEFAULT 0,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');

        DB::statement('CREATE TABLE IF NOT EXISTS bus_event_keys (
            event_key CHAR(40) NOT NULL,
            consumer_group VARCHAR(50) NOT NULL,
            event_offset BIGINT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (event_key),
            INDEX idx_bek_consumer (consumer_group)
        )');

        DB::statement('CREATE TABLE IF NOT EXISTS bus_dlq (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            consumer_group VARCHAR(50) NOT NULL,
            topic VARCHAR(100),
            event_key VARCHAR(255),
            event_offset BIGINT,
            error TEXT,
            attempts INT DEFAULT 3,
            event_payload JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_bd_group (consumer_group),
            INDEX idx_bd_time (created_at)
        )');

        DB::statement('CREATE TABLE IF NOT EXISTS ml_predictions (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            pipeline_id INT NOT NULL,
            score DECIMAL(5,4),
            band VARCHAR(10),
            edge_score DECIMAL(5,4),
            correlation_id VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ml_pipeline (pipeline_id),
            INDEX idx_ml_correlation (correlation_id)
        )');

        DB::statement('CREATE TABLE IF NOT EXISTS algorithm_results (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            pipeline_id INT NOT NULL,
            edge_score DECIMAL(5,4),
            mechanism VARCHAR(50),
            value_kes DECIMAL(15,2),
            correlation_id VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ar_pipeline (pipeline_id)
        )');
    }

    public function down(): void
    {
        foreach (['algorithm_results', 'ml_predictions', 'bus_dlq', 'bus_event_keys', 'consumer_offsets', 'bus_events'] as $t) {
            DB::statement("DROP TABLE IF EXISTS {$t}");
        }
    }
};