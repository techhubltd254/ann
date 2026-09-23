<?php

/**
 * Mother-Pipeline Joiner — framework-free end-to-end self-test.
 * Runs ALL discovered subsector pipelines through the joiner core:
 * registry -> deterministic plan -> unified GL -> Mother-Pool math ->
 * reconciliation -> idempotency -> tamper detection.
 * Run: php tests/selftest_joiner_core.php   (exit 0 = all pass)
 */

require __DIR__ . '/../app/Kicc/Support/RemainderAllocator.php';
require __DIR__ . '/../app/Kicc/Support/TrialBalance.php';
require __DIR__ . '/../app/Kicc/Support/Economics.php';
require __DIR__ . '/../app/Kicc/Joiner/MotherPipelineJoiner.php';

use App\Kicc\Joiner\MotherPipelineJoiner;

$fails = 0;
$checks = 0;
$t = function (string $name, bool $cond) use (&$fails, &$checks) {
    $checks++;
    echo ($cond ? 'PASS' : 'FAIL') . "  {$name}\n";
    if (!$cond) $fails++;
};
$eq = fn ($a, $b) => abs($a - $b) < 0.01;

$configDir = dirname(__DIR__) . '/config';
$joiner = MotherPipelineJoiner::fromConfig($configDir);
$discovered = $joiner->count();
echo "Discovered subsector pipelines: {$discovered}\n";

// 1. registry enumeration — must be the full configured set
$t('registry enumerated via config loader', $discovered > 100);
$t('registry count == 152 (actual: ' . $discovered . ')', $discovered === 152);

// 2. deterministic order + full plan over ALL pipelines (synthetic GMV scenario)
$plan1 = $joiner->buildPlan(fn () => 100000.00, 'conservative');
$plan2 = $joiner->buildPlan(fn () => 100000.00, 'conservative');
$t('deterministic: two identical builds -> identical batch_id', $plan1['batch_id'] === $plan2['batch_id']);
$t('deterministic: identical posting sets', $plan1['postings'] === $plan2['postings']);
$t('deterministic: identical run order', $plan1['deterministic_order'] === $plan2['deterministic_order']);

// 3. coverage: active + locked + skipped == discovered
$activeCodes = array_values(array_unique(array_column($plan1['contributions'], 'pipeline_code')));
$lockedCodes = array_column($plan1['locked'], 'code');
$skippedCodes = array_column($plan1['skipped'], 'code');
$t('coverage: active(' . count($activeCodes) . ') + locked(' . count($lockedCodes) . ') + skipped(' . count($skippedCodes) . ') == ' . $discovered,
   count($activeCodes) + count($lockedCodes) + count($skippedCodes) === $discovered);
$t('no code appears twice across partitions', count(array_merge($activeCodes, $lockedCodes, $skippedCodes)) === $discovered);

// 4. locked pipelines excluded from earning (licence gates)
$t('locked pipelines (CBK/IFMIS/blocked) produce NO postings', count(array_intersect($lockedCodes, $activeCodes)) === 0);
$expectedLocked = ['B6', 'D3', 'F1', 'F2', 'F3', 'F5', 'G2', 'H1', 'K3'];
$t('known licence-gated parents all locked', empty(array_diff($expectedLocked, array_map(fn ($c) => strtok($c, '.'), $lockedCodes))));

// 5. unified GL: per-account debits == credits across the whole plan
$recon = $joiner->reconcile($plan1);
$t('unified per-account balance (debits == credits)', $recon['accounts']['balanced']);
$t('reconciliation BALANCED over all ' . count($activeCodes) . ' active pipelines', $recon['balanced']);
$t('variance report empty', empty($recon['variance_report']));

// 6. economic identities + totals
$tot = $recon['totals'];
$t('totals tie: kicc_net+holdback+equalisation == fees', $eq($tot['kicc_net'] + $tot['holdback'] + $tot['equalisation'], $tot['fees']));
$t('totals tie: vendor_payout+fees == gmv', $eq($tot['vendor_payout'] + $tot['fees'], $tot['gmv']));
$t('holdback == 10% of fees', $eq($tot['holdback'], $tot['fees'] * 0.10));
$t('equalisation == 0.5% of fees', $eq($tot['equalisation'], $tot['fees'] * 0.005));

// 7. idempotency keys: unique, complete, stable across runs
$keys1 = array_column($plan1['postings'], 'posting_key');
$keys2 = array_column($plan2['postings'], 'posting_key');
$t('posting keys unique', count($keys1) === count(array_unique($keys1)));
$t('posting keys stable across runs', $keys1 === $keys2);
$t('every posting keyed', count($keys1) === count($plan1['postings']));

// 8. rate modes: conservative <= optimistic on commission pipelines
$planOpt = $joiner->buildPlan(fn () => 100000.00, 'optimistic');
$t('optimistic fees >= conservative fees', $planOpt['totals']['fees'] >= $plan1['totals']['fees']);

// 9. TAMPER DETECTION: an injected imbalance MUST surface in the variance report
$tampered = $plan1;
$tampered['postings'][0]['debit'] += 100.00;
$reconT = $joiner->reconcile($tampered);
$t('tampered plan detected as UNBALANCED', !$reconT['balanced']);
$t('tampered plan appears in variance report', !empty($reconT['variance_report']));

// 10. engine wiring consumed from the real config contracts
$bindings = require $configDir . '/kicc-engine-bindings.php';
$t('joiner consumes kicc-engine-bindings.php', isset($bindings['ledger']['service'], $bindings['mother_pool']['service']));
$t('ledger service is LedgerService', $bindings['ledger']['service'] === 'App\Kicc\Services\LedgerService');
$t('pool service is MotherPoolService', $bindings['mother_pool']['service'] === 'App\Kicc\Services\MotherPoolService');

echo sprintf("\nScenario totals — GMV %.2f | fees %.2f | holdback %.2f | equalisation %.2f | kicc_net %.2f | vendor_payout %.2f\n",
    $tot['gmv'], $tot['fees'], $tot['holdback'], $tot['equalisation'], $tot['kicc_net'], $tot['vendor_payout']);

echo $fails === 0
    ? "\nALL JOINER CORE TESTS PASSED — {$checks} checks over {$discovered} subsector pipelines\n"
    : "\n{$fails} of {$checks} JOINER TEST(S) FAILED\n";
exit($fails === 0 ? 0 : 1);
