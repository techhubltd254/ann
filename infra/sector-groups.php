<?php
/**
 * Sector cleanup + canonical grouping for the KICC platform.
 *
 * The sectors table was scraped from 47 county websites and contains:
 *  - junk: news headlines ("Governor Kihika Revives Agriculture…"), social links,
 *    dashboards, charters, company names, events, documents
 *  - massive duplication: every county names the same department differently
 *    ("Finance & Economic Planning", "Finance, Budget, Strategy and Economic Planning", …)
 *  - HTML entities (&#038;, &amp;)
 *
 * This script:
 *  1. marks junk as inactive (is_active=0) — never rendered anywhere again
 *  2. assigns every active sector to a canonical major group (sector_group column)
 *  3. dedupes exact name duplicates (keeps lowest id, deactivates the rest)
 *
 * Usage: php sector-groups.php [--apply]   (default: dry-run report)
 */

$apply = in_array('--apply', $argv ?? [], true);

$pdo = new PDO(
    'mysql:host=gateway01.eu-central-1.prod.aws.tidbcloud.com;port=4000;dbname=kicc;charset=utf8mb4',
    '28dbcDfwh5hEbSc.root', 'D8trCZaYhqZWo5Vq',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::MYSQL_ATTR_SSL_CA => '/etc/ssl/certs/ca-certificates.crt']
);

// ---- Canonical major groups (order = display order) ------------------------
$GROUPS = [
    'agriculture'     => ['Agriculture, Livestock & Fisheries', '🌾'],
    'health'          => ['Health & Sanitation', '🏥'],
    'education'       => ['Education, Youth & Sports', '🎓'],
    'trade'           => ['Trade, Industry & Cooperatives', '🛍️'],
    'tourism'         => ['Tourism, Culture & Heritage', '🏖️'],
    'infrastructure'  => ['Infrastructure, Transport & Public Works', '🚧'],
    'lands'           => ['Lands, Housing & Urban Development', '🏘️'],
    'water'           => ['Water, Environment & Climate', '💧'],
    'energy'          => ['Energy', '⚡'],
    'ict'             => ['ICT & Digital Economy', '💻'],
    'finance'         => ['Finance & Economic Planning', '💰'],
    'public-service'  => ['Public Service & Administration', '🏛️'],
    'social'          => ['Gender & Social Services', '🤝'],
];

// Keyword rules — first match wins (order matters: specific before broad).
$RULES = [
    'agriculture'    => ['agricultur', 'livestock', 'fisheries', 'farming', 'farm', 'irrigation', 'veterinary', 'crop', 'agribusiness', 'agri '],
    'health'         => ['health', 'medical', 'sanitation', 'hospital', 'clinic'],
    'education'      => ['education', 'technical training', 'vocational', 'school', 'university', 'college', 'youth', 'sport', 'polytechnic'],
    'trade'          => ['trade', 'industr', 'commerce', 'cooperative', 'co-operative', 'enterprise', 'manufactur', 'msme', 'investment', 'business'],
    'tourism'        => ['tourism', 'culture', 'heritage', 'wildlife', 'creative', 'arts', 'museum', 'history'],
    'infrastructure' => ['infrastructure', 'road', 'transport', 'public works', 'logistics', 'housing & urban planning' => null],
    'lands'          => ['land', 'housing', 'urban', 'physical planning', 'municipalit', 'serikali mtaani', 'real estate'],
    'water'          => ['water', 'environment', 'natural resource', 'climate', 'forestry', 'sewerage'],
    'energy'         => ['energy'],
    'ict'            => ['ict', 'digital', 'e-government', 'technology', 'innovation', 'information communication'],
    'finance'        => ['financ', 'treasury', 'budget', 'economic planning', 'revenue', 'accounting'],
    'public-service' => ['public service', 'administration', 'devolution', 'county secretary', 'public service board', 'governance', 'legal', 'citizen participation', 'disaster', 'inspectorate', 'public service management'],
    'social'         => ['gender', 'social service', 'social welfare', 'community development', 'social protection', 'talent management'],
];

// Junk patterns — news, events, nav junk, companies, documents.
$JUNK = [
    'governor', 'project set to', 'achieved', 'revives', 'strengthens', 'steps up', 'expo', 'festival', 'summit',
    'conference', 'follow us', 'social media', 'we are social', 'dashboard', 'charter', 'documents', 'helpdesk',
    'contact', 'i am committed', 'take a look', 'narwassco', 'foundation', 'lapfund', 'tea estates',
    'supply and delivery', 'land rates', '(pfm)', 'guidelines', 'implementation', 'in pictures',
    'mou', 'signs', 'boost', 'launches', 'unveils', 'empowers thousands', 'investment in', 'push to protect',
    'partnership', 'database', 'o&m', 'center', 'centre',
];

function classify(string $name, array $rules): ?string
{
    $n = strtolower($name);
    foreach ($rules as $group => $keywords) {
        foreach ($keywords as $kw => $v) {
            $kw = is_int($kw) ? $v : $kw;   // support keyed entries
            if ($kw && str_contains($n, $kw)) return $group;
        }
    }
    return null;
}

function isJunk(string $name, array $junk): bool
{
    $n = strtolower($name);
    if (mb_strlen($n) > 100) return true;                       // headlines
    if (preg_match('/\b(19|20)\d{2}\b/', $n)) return true;      // years = news/events
    foreach ($junk as $j) if (str_contains($n, $j)) return true;
    return false;
}

// Fetch sectors
$rows = $pdo->query('SELECT id, name, slug, is_active FROM sectors ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);

$report = ['junk' => [], 'dupes' => [], 'grouped' => [], 'ungrouped' => []];
$seenNames = [];

foreach ($rows as $r) {
    $name = html_entity_decode($r['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $nameNorm = mb_strtolower(trim(preg_replace('/\s+/', ' ', $name)));

    if (isJunk($name, $JUNK)) {
        $report['junk'][] = $name;
        continue;
    }
    if (isset($seenNames[$nameNorm])) {
        $report['dupes'][] = $name . ' (dup of #' . $seenNames[$nameNorm] . ')';
        continue;
    }
    $seenNames[$nameNorm] = $r['id'];

    $group = classify($name, $RULES);
    if ($group) $report['grouped'][$group][] = $name;
    else $report['ungrouped'][] = $name;
}

// ---- report ----------------------------------------------------------------
echo "=== JUNK to deactivate (" . count($report['junk']) . ") ===\n";
foreach (array_slice($report['junk'], 0, 10) as $n) echo "  ✗ $n\n";
echo "=== DUPLICATES to deactivate (" . count($report['dupes']) . ") ===\n";
foreach (array_slice($report['dupes'], 0, 5) as $n) echo "  ✗ $n\n";
echo "=== GROUPED ===\n";
foreach ($report['grouped'] as $g => $names) {
    echo sprintf("  %-16s %3d  %s\n", $g, count($names), $GROUPS[$g][1] . ' ' . $GROUPS[$g][0]);
}
echo "=== UNGROUPED (active but no group — review) (" . count($report['ungrouped']) . ") ===\n";
foreach (array_slice($report['ungrouped'], 0, 15) as $n) echo "  ? $n\n";

if (!$apply) {
    echo "\nDRY RUN — pass --apply to write.\n";
    exit(0);
}

// ---- apply -----------------------------------------------------------------
// 1. ensure the grouping column exists
$col = $pdo->query("SHOW COLUMNS FROM sectors LIKE 'sector_group'")->fetch();
if (!$col) {
    $pdo->exec("ALTER TABLE sectors ADD COLUMN sector_group varchar(40) NULL AFTER description");
    echo "added sectors.sector_group column\n";
}

$junkCount = 0; $dupeCount = 0; $groupCount = 0;
$seen = [];
foreach ($rows as $r) {
    $name = html_entity_decode($r['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $nameNorm = mb_strtolower(trim(preg_replace('/\s+/', ' ', $name)));

    if (isJunk($name, $JUNK)) {
        $pdo->prepare('UPDATE sectors SET is_active=0, isActive=0 WHERE id=?')->execute([$r['id']]);
        $junkCount++;
        continue;
    }
    if (isset($seen[$nameNorm])) {
        $pdo->prepare('UPDATE sectors SET is_active=0, isActive=0 WHERE id=?')->execute([$r['id']]);
        $dupeCount++;
        continue;
    }
    $seen[$nameNorm] = $r['id'];
    $group = classify($name, $RULES);
    if ($group) {
        $pdo->prepare('UPDATE sectors SET sector_group=?, name=? WHERE id=?')
            ->execute([$group, $name, $r['id']]);
        $groupCount++;
    }
}
echo "\nAPPLIED: $junkCount junk deactivated, $dupeCount dupes deactivated, $groupCount sectors grouped.\n";
echo 'Active sectors now: ' . $pdo->query('SELECT COUNT(*) FROM sectors WHERE is_active=1')->fetchColumn() . "\n";
