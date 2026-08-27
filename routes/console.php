<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Travel recommendation engine — refresh origin-market weather hourly,
// fire n8n SEO campaigns when a cold market is detected.
Schedule::command('travel:refresh-recommendations')->hourly();

// Build recommendation engine nightly from user behavior data.
Schedule::command('recommendations:build')->dailyAt('02:00');

// Rebuild semantic-search embeddings (new/changed content only).
Schedule::command('embeddings:build')->dailyAt('02:30');

// Two-score vendor grading (Trust earned + Visibility purchased) nightly.
Schedule::command('vendors:score')->dailyAt('03:00');

// Subscription billing cycles + invoices (16% VAT) daily.
Schedule::command('billing:run')->dailyAt('04:00');

// Anomaly sweeps (brute force, card testing, refund fraud) every 15 min.
Schedule::command('anomalies:detect')->everyFifteenMinutes();

// Adaptive HLS video pipeline — every new upload is picked up by the
// MediaAssetObserver -> GenerateHlsJob chain; this sweeper catches any
// videos missed before the observer existed (backfill), 3 at a time so
// the 2-core VPS is never saturated.
Schedule::command('media:sweep-hls --limit=3')->everyFiveMinutes()->withoutOverlapping();

// Predictive demand/trend rollups nightly.
Schedule::command('analytics:trends')->dailyAt('04:30');

// DBA cadence: weekly index/health audit (Sundays 05:00).
Schedule::command('dba:index-audit')->weeklyOn(0, '05:00');

// Off-host DB backups -> Cloudflare R2 (mydumper dump, 48-hourly / 14-daily / 12-monthly retention).
Schedule::exec('/opt/kicc-laravel/scripts/backup-db.sh hourly')->hourlyAt(30);
Schedule::exec('/opt/kicc-laravel/scripts/backup-db.sh daily')->dailyAt('02:45');

// Automated DB restore test — verifies the latest backup is restorable (Sundays 06:30).
Schedule::exec('/opt/kicc-laravel/scripts/restore-test.sh')->weeklyOn(0, '06:30');

// Elasticsearch index rebuild (runs silently when ES is unavailable).
Schedule::command('search:index-es')->weeklyOn(0, '06:00');
