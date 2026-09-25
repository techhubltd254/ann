// lib/bus-sql.mjs — Node.js MySQL/TiDB client for the pipeline bus.
// Replaces JSONL file appends with SQL INSERT for durability, queryability
// and concurrent access at scale.
//
// Requires: npm install mysql2  (TiDB speaks the MySQL protocol on :4000)
//
// Env vars (typically set in the systemd unit or docker-compose):
//   TIDB_HOST, TIDB_PORT, TIDB_USER, TIDB_PASSWORD, TIDB_DATABASE
import mysql from 'mysql2/promise';

let pool = null;

async function getPool() {
    if (pool) return pool;

    const config = {
        host: env('TIDB_HOST', 'gateway01.eu-central-1.prod.aws.tidbcloud.com'),
        port: parseInt(env('TIDB_PORT', '4000'), 10),
        user: env('TIDB_USER', env('TIDB_USERNAME', '')),
        password: env('TIDB_PASSWORD', ''),
        database: env('TIDB_DATABASE', 'kicc'),
        ssl: env('TIDB_SSL_VERIFY', 'false') === 'true'
            ? { rejectUnauthorized: true }
            : { rejectUnauthorized: false },
        connectTimeout: 10000,
        maxReconnects: 3,
        charset: 'utf8mb4',
    };

    pool = mysql.createPool(config);
    log.info('bus-sql: TiDB pool created');
    return pool;
}

/** Insert one bus event into the SQL journal. Returns the auto-increment id. */
export async function insertEvent(topic, payload, metadata = {}) {
  try {
        const db = await getPool();
        const [result] = await db.execute(
            `INSERT INTO bus_events (topic, payload, metadata, idempotency_key, correlation_id, causation_id, hop, published_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())`,
            [
                topic,
                JSON.stringify(payload),
                JSON.stringify(metadata),
                metadata.idempotencyKey || null,
                metadata.correlationId || null,
                metadata.causationId || null,
                metadata.hop || 0,
            ]
        );
        return result.insertId;
    } catch (e) {
        log.warn(`bus-sql: insert failed (${topic}): ${e.message}`);
        return null;
    }
}

/**
 * Fetch events since a given offset. Returns { rows, maxId }.
 * This is what replaces fs.readFileSync(bus.jsonl) in the consumer.
 */
export async function fetchEventsSince(offset = 0, limit = 200, topics = null) {
    

    try {
        const db = await getPool();
        let sql = 'SELECT id, topic, payload, metadata, idempotency_key, correlation_id, causation_id, hop, published_at FROM bus_events WHERE id > ?';
        const params = [offset];

        if (topics && topics.length > 0 && !topics.includes('*')) {
            // Build parameterized IN clause
            const placeholders = topics.map(() => '?').join(',');
            sql += ` AND topic IN (${placeholders})`;
            params.push(...topics);
        }

        sql += ' ORDER BY id ASC LIMIT ?';
        params.push(limit);

        const [rows] = await db.execute(sql, params);

        const parsed = rows.map(r => ({
            id: r.id,
            topic: r.topic,
            payload: typeof r.payload === 'string' ? JSON.parse(r.payload) : r.payload,
            metadata: typeof r.metadata === 'string' ? JSON.parse(r.metadata) : (r.metadata || {}),
            idempotency_key: r.idempotency_key,
            correlation_id: r.correlation_id,
            causation_id: r.causation_id,
            hop: r.hop || 0,
            published_at: r.published_at,
        }));

        const maxId = parsed.length > 0 ? parsed[parsed.length - 1].id : offset;
        return { rows: parsed, maxId };
    } catch (e) {
        log.error(`bus-sql: fetch failed: ${e.message}`);
        return { rows: [], maxId: offset };
    }
}

/** Return the total row count (for lag computation). */
export async function countEvents() {
    try {
        const db = await getPool();
        const [[r]] = await db.execute('SELECT COUNT(*) as c FROM bus_events');
        return r.c;
    } catch {
        return 0;
    }
}

/** Update consumer offset (checkpoint). */
export async function updateOffset(consumerGroup, ackOffset, processedCount = 0, dlqCount = 0) {
    try {
        const db = await getPool();
        await db.execute(
            `INSERT INTO consumer_offsets (consumer_group, ack_offset, processed_count, dlq_count, updated_at)
             VALUES (?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE ack_offset = VALUES(ack_offset), processed_count = VALUES(processed_count), dlq_count = VALUES(dlq_count), updated_at = NOW()`,
            [consumerGroup, ackOffset, processedCount, dlqCount]
        );
    } catch (e) {
        log.warn(`bus-sql: offset update failed (${consumerGroup}): ${e.message}`);
    }
}

/** Load consumer offset. */
export async function loadOffset(consumerGroup) {
    try {
        const db = await getPool();
        const [[r]] = await db.execute(
            'SELECT ack_offset, processed_count, dlq_count FROM consumer_offsets WHERE consumer_group = ?',
            [consumerGroup]
        );
        return r || { ack_offset: 0, processed_count: 0, dlq_count: 0 };
    } catch {
        return { ack_offset: 0, processed_count: 0, dlq_count: 0 };
    }
}

/** Check if an idempotency key exists for a consumer group. */
export async function hasEventKey(eventKey, consumerGroup) {
    try {
        const db = await getPool();
        const [[r]] = await db.execute(
            'SELECT 1 FROM bus_event_keys WHERE event_key = ? AND consumer_group = ?',
            [eventKey, consumerGroup]
        );
        return !!r;
    } catch {
        return false;
    }
}

/** Insert an idempotency guard key. */
export async function insertEventKey(eventKey, consumerGroup, eventOffset) {
    try {
        const db = await getPool();
        await db.execute(
            'INSERT IGNORE INTO bus_event_keys (event_key, consumer_group, event_offset) VALUES (?, ?, ?)',
            [eventKey, consumerGroup, eventOffset]
        );
    } catch (e) {
        log.warn(`bus-sql: event key insert failed: ${e.message}`);
    }
}

/** Insert a dead-lettered event. */
export async function insertDlq(consumerGroup, topic, eventKey, eventOffset, error, attempts, payload) {
    try {
        const db = await getPool();
        await db.execute(
            `INSERT INTO bus_dlq (consumer_group, topic, event_key, event_offset, error, attempts, event_payload, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())`,
            [consumerGroup, topic, eventKey, eventOffset, error, attempts, JSON.stringify(payload)]
        );
    } catch (e) {
        log.warn(`bus-sql: dlq insert failed: ${e.message}`);
    }
}

/** Fetch DLQ rows for status reporting. */
export async function fetchDlq(consumerGroup, limit = 100) {
    try {
        const db = await getPool();
        const [rows] = await db.execute(
            'SELECT * FROM bus_dlq WHERE consumer_group = ? ORDER BY created_at DESC LIMIT ?',
            [consumerGroup, limit]
        );
        return rows.map(r => ({
            ...r,
            event_payload: typeof r.event_payload === 'string' ? JSON.parse(r.event_payload) : r.event_payload,
        }));
    } catch { return []; }
}

/** Insert ML prediction. */
export async function insertPrediction(pipelineId, score, band, edgeScore, correlationId) {
    try {
        const db = await getPool();
        await db.execute(
            'INSERT INTO ml_predictions (pipeline_id, score, band, edge_score, correlation_id) VALUES (?, ?, ?, ?, ?)',
            [pipelineId, score, band, edgeScore, correlationId || null]
        );
    } catch (e) {
        log.warn(`bus-sql: prediction insert failed: ${e.message}`);
    }
}

/** Insert algorithm result. */
export async function insertAlgorithmResult(pipelineId, edgeScore, mechanism, valueKes, correlationId) {
    try {
        const db = await getPool();
        await db.execute(
            'INSERT INTO algorithm_results (pipeline_id, edge_score, mechanism, value_kes, correlation_id) VALUES (?, ?, ?, ?, ?)',
            [pipelineId, edgeScore, mechanism || null, valueKes || 0, correlationId || null]
        );
    } catch (e) {
        log.warn(`bus-sql: algorithm result insert failed: ${e.message}`);
    }
}

/** Close the connection pool. */
export async function closePool() {
    if (pool) {
        await pool.end();
        pool = null;
        log.info('bus-sql: pool closed');
    }
}