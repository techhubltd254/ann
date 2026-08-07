<?php
/**
 * Reconcile sectors between the SQLite source dump (218 rows) and TiDB prod (99 rows).
 *
 * Findings encoded here (2026-08-03 audit):
 *  - TiDB `sectors` had 99/218 rows; sync partially failed (dual snake_case/camelCase
 *    NOT NULL columns without defaults — Laravel-side inserts crash).
 *  - Names/descriptions contain raw HTML entities (&#038;) from unescaped scraping.
 *  - Slugs contain "-038-" (slugified "&#038;") — regenerated cleanly here.
 *  - sector_entities is 100% synthetic placeholder data — intentionally NOT synced.
 *
 * Usage: php reconcile-sectors.php [--apply]   (default: dry-run report)
 */

$apply = in_array('--apply', $argv ?? [], true);

$lite = new PDO('sqlite:/home/kicc/Desktop/kicc/kicc-portable-admin/database/database.sqlite');
$lite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$tidb = new PDO(
    'mysql:host=gateway01.eu-central-1.prod.aws.tidbcloud.com;port=4000;dbname=kicc;charset=utf8mb4',
    '28dbcDfwh5hEbSc.root', 'D8trCZaYhqZWo5Vq',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::MYSQL_ATTR_SSL_CA => '/etc/ssl/certs/ca-certificates.crt']
);

$clean = fn (?string $s): string => html_entity_decode((string) $s, ENT_QUOTES | ENT_HTML5, 'UTF-8');

/** Scraper over-capture filter: news headlines, nav junk, and footer text are NOT sectors.
 *  NOTE: real Kenyan county departments have long combined names (up to ~95 chars) — length alone is not noise. */
$isNoise = function (string $name): bool {
    if (mb_strlen($name) > 100) return true;
    if (preg_match('/\b(signs?|boosts?|launche\w*|unveils?|mou|marks?|hosts?|wins?|gets?|set to|rolls? out|kicks? off|commissions?|flags? off|receives?|donates?|pledges?|in pictures|festival|expo|summit|conference)\b/i', $name)) return true;
    if (preg_match('/^(social info|quick links?|read more|home|menu|contact us?|news|events?|gallery|downloads?|our services|about us|more info)$/i', $name)) return true;
    if (preg_match('/\b(19|20)\d{2}\b/', $name)) return true; // years → news items
    return false;
};

$slugify = function (string $name): string {
    $s = str_replace('&', ' and ', $name);
    $s = strtolower(trim((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $s), '-'));
    return substr($s, 0, 240);
};

$tidbSlugs = $tidb->query('SELECT id, slug FROM sectors')->fetchAll(PDO::FETCH_KEY_PAIR);
$tidbIds = array_keys($tidbSlugs);
$usedSlugs = array_values($tidbSlugs); // collision registry (same dept name appears in many counties)

$inserts = []; $updates = []; $noise = [];
foreach ($lite->query('SELECT * FROM sectors ORDER BY id') as $r) {
    $name = $clean($r['name']);
    $desc = $clean($r['description'] ?? '');
    $slug = $slugify($name) ?: $r['slug'];
    if (!in_array((int) $r['id'], $tidbIds, true) && $isNoise($name)) {
        $noise[] = $name;
        continue;
    }
    // Unique-slug resolution: same department name exists across counties — disambiguate by id.
    if (in_array($slug, $usedSlugs, true)) {
        $slug = substr($slug, 0, 230) . '-' . (int) $r['id'];
    }
    $usedSlugs[] = $slug;
    $row = [
        'id' => (int) $r['id'],
        'name' => $name,
        'slug' => $slug,
        'code' => $r['code'] ?: ('S' . strtoupper(substr(md5($slug), 0, 7))),
        'emoji' => $r['emoji'] ?: '',
        'description' => $desc,
        'parent_id' => $r['parent_id'] ?: null,
        'icon' => $r['icon'] ?: '',
        'is_active' => (int) ($r['is_active'] ?? 1),
        'sort_order' => (int) ($r['sort_order'] ?? 0),
        'created_at' => $r['created_at'] ?: date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ];
    if (in_array((int) $r['id'], $tidbIds, true)) {
        $updates[] = $row; // fix entities/slug in place
    } elseif (!in_array($r['slug'], $tidbSlugs, true) && !in_array($slug, $tidbSlugs, true)) {
        $inserts[] = $row;  // genuinely missing
    }
}

echo "INSERT candidates: " . count($inserts) . "\n";
echo "UPDATE candidates (entity/slug cleanup): " . count($updates) . "\n";
echo "NOISE rejected (news/nav junk): " . count($noise) . "\n";
foreach (array_slice($noise, 0, 4) as $n) echo "  ✗ $n\n";
foreach (array_slice($inserts, 0, 5) as $r) echo "  + [{$r['id']}] {$r['name']} ({$r['slug']})\n";

if (!$apply) {
    echo "\nDRY RUN — pass --apply to write to TiDB.\n";
    exit(0);
}

$tidb->beginTransaction();
try {
    $ins = $tidb->prepare(
        'INSERT INTO sectors (id, name, slug, code, emoji, description, parent_id, icon, is_active, sort_order,
                               created_at, updated_at, centralId, contentHash, isActive, localId, parentId, sortOrder, syncStatus, syncedAt)
         VALUES (:id, :name, :slug, :code, :emoji, :description, :parent_id, :icon, :is_active, :sort_order,
                 :created_at, :updated_at, :id, :content_hash, 1, :local_id, :parent_id, :sort_order, :sync_status, :synced_at)'
    );
    foreach ($inserts as $r) {
        $ins->execute($r + [
            'content_hash' => md5($r['name'] . $r['slug']),
            'local_id' => 'sqlite-' . $r['id'],
            'sync_status' => 'synced',
            'synced_at' => date('Y-m-d H:i:s'),
        ]);
    }
    $upd = $tidb->prepare('UPDATE sectors SET name = :name, description = :description, slug = :slug, updated_at = :updated_at WHERE id = :id');
    foreach ($updates as $r) {
        $upd->execute(['id' => $r['id'], 'name' => $r['name'], 'description' => $r['description'], 'slug' => $r['slug'], 'updated_at' => $r['updated_at']]);
    }
    $tidb->commit();
    echo "\nAPPLIED: " . count($inserts) . " inserts, " . count($updates) . " updates.\n";
    echo 'TiDB sectors now: ' . $tidb->query('SELECT COUNT(*) FROM sectors')->fetchColumn() . "\n";
} catch (Throwable $e) {
    $tidb->rollBack();
    fwrite(STDERR, 'FAILED, rolled back: ' . $e->getMessage() . "\n");
    exit(1);
}
