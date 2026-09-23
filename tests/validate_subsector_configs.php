<?php

/**
 * Validation suite: loads every subsector config through the real registry
 * (config/kicc/subsectors/index.php) and asserts required keys, parent
 * linkage, engine bindings, earning-lock consistency, and economics models.
 * Run: php tests/validate_subsector_configs.php   (exit 0 = all valid)
 */

$fail = 0;
$checks = 0;
$t = function (string $name, bool $cond) use (&$fail, &$checks) {
    $checks++;
    if (!$cond) { echo "FAIL  {$name}\n"; $fail++; }
};

$parents  = require __DIR__ . '/../config/kicc-pipelines.php';
$bindings = require __DIR__ . '/../config/kicc-engine-bindings.php';
$subs     = require __DIR__ . '/../config/kicc/subsectors/index.php';

$bySector = [];
foreach ($parents as $p) { $bySector[$p['sector']][$p['code']] = $p; }

$t('parent registry loads with 50 pipelines', count($parents) === 50);
$t('subsector registry loads with >100 entries', count($subs) > 100);
$t('engine bindings file loads', is_array($bindings) && isset($bindings['ledger'], $bindings['mother_pool']));

$codes = [];
$perSector = [];
foreach ($subs as $e) {
    $c = $e['code'] ?? '??';
    $t("{$c} code unique", !isset($codes[$c]));
    $codes[$c] = true;
    $perSector[$e['sector'] ?? '?'] = ($perSector[$e['sector'] ?? '?'] ?? 0) + 1;

    foreach (['code','parent','sector','subsector','slug','phase','status','economics','regulators','tables','kill_criteria','engine'] as $k) {
        $t("{$c} has key '{$k}'", array_key_exists($k, $e));
    }
    // parent linkage: parent must exist in the SAME sector of the parent registry
    $t("{$c} parent {$e['parent']} exists in sector {$e['sector']}",
       isset($bySector[$e['sector']][$e['parent']]) && $bySector[$e['sector']][$e['parent']]['code'] === $e['parent']);
    // inherited phase/status must equal the parent's
    if (isset($bySector[$e['sector']][$e['parent']])) {
        $pp = $bySector[$e['sector']][$e['parent']];
        $t("{$c} phase inherited", $e['phase'] === $pp['phase']);
        $t("{$c} status inherited", $e['status'] === $pp['status']);
    }

    // engine wiring — General Ledger
    $L = $e['engine']['ledger'] ?? [];
    $t("{$c} ledger service === LedgerService", ($L['service'] ?? null) === $bindings['ledger']['service']);
    $t("{$c} ledger pipeline_code === own code", ($L['pipeline_code'] ?? null) === $c);
    $t("{$c} hold_account === escrow_clearing", ($L['hold_account'] ?? null) === $bindings['ledger']['hold_account']);
    $t("{$c} payable_account === merchant_payable", ($L['payable_account'] ?? null) === $bindings['ledger']['payable_account']);
    $t("{$c} fee_credit_account === fees_income", ($L['fee_credit_account'] ?? null) === $bindings['ledger']['fee_credit_account']);
    $t("{$c} lifecycle === full escrow chain", ($L['lifecycle'] ?? []) === $bindings['ledger']['lifecycle']);

    // engine wiring — Mother-Pool
    $M = $e['engine']['mother_pool'] ?? [];
    $t("{$c} pool service === MotherPoolService", ($M['service'] ?? null) === $bindings['mother_pool']['service']);
    $t("{$c} holdback_rate === 0.10", ($M['holdback_rate'] ?? null) === $bindings['mother_pool']['holdback_rate']);
    $t("{$c} equalisation_rate === 0.005", ($M['equalisation_rate'] ?? null) === $bindings['mother_pool']['equalisation_rate']);
    $t("{$c} quality_service === QualityScoreService", ($M['quality_service'] ?? null) === $bindings['mother_pool']['quality_service']);

    // earning lock consistency with status
    $locked = in_array($e['status'], $bindings['earning_locked_statuses'], true);
    $t("{$c} earning_locked consistent with status '{$e['status']}'", (bool)($L['earning_locked'] ?? null) === $locked);

    // economics model validity
    $t("{$c} economics model valid", in_array($e['economics']['model'] ?? '', ['commission','flat_fee','rate_note'], true));
}

echo "\nCoverage by sector:\n";
ksort($perSector);
foreach ($perSector as $s => $n) { echo sprintf("  %-22s %d\n", $s, $n); }
echo sprintf("  %-22s %d\n", 'TOTAL', count($subs));

echo $fail === 0
    ? "\nVALIDATION PASSED — {$checks} checks across " . count($subs) . " subsector configs\n"
    : "\nVALIDATION FAILED — {$fail} of {$checks} checks failed\n";
exit($fail === 0 ? 0 : 1);
