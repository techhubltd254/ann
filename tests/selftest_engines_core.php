<?php

/**
 * Framework-free self-test of the pure engine logic:
 * RemainderAllocator, TrialBalance, Economics.
 * Run: php tests/selftest_engines_core.php   (exit 0 = all pass)
 */

require __DIR__ . '/../app/Kicc/Support/RemainderAllocator.php';
require __DIR__ . '/../app/Kicc/Support/TrialBalance.php';
require __DIR__ . '/../app/Kicc/Support/Economics.php';

use App\Kicc\Support\Economics;
use App\Kicc\Support\RemainderAllocator;
use App\Kicc\Support\TrialBalance;

$fails = 0;
$t = function (string $name, bool $cond) use (&$fails) {
    echo ($cond ? 'PASS' : 'FAIL') . "  {$name}\n";
    if (!$cond) $fails++;
};
$eq = fn ($a, $b) => abs($a - $b) < 0.001;

// --- RemainderAllocator -------------------------------------------------
$r = RemainderAllocator::allocate(100.00, ['a' => 3334, 'b' => 3333, 'c' => 3333]);
$t('allocate: parts sum exactly to total', $eq(array_sum($r), 100.00));

$r2 = RemainderAllocator::allocate(0.01, ['a' => 1, 'b' => 1, 'c' => 1]);
$t('allocate: single cent to exactly one beneficiary', $eq(array_sum($r2), 0.01) && count(array_filter($r2)) === 1);

$r3 = RemainderAllocator::allocate(1000000.55, ['x' => 61, 'y' => 39]);
$t('allocate: large odd amount, exact sum', $eq(array_sum($r3), 1000000.55));
$t('allocate: weighted shares respected (~61/39)', $r3['x'] > $r3['y']);

$r4 = RemainderAllocator::allocate(134.25, ['1:C1' => 89.5, '2:B1' => 44.75]);
$t('allocate: deterministic (same input twice → identical)',
   $r4 === RemainderAllocator::allocate(134.25, ['1:C1' => 89.5, '2:B1' => 44.75]));

// --- TrialBalance -------------------------------------------------------
$tb = TrialBalance::rollup([
    ['code' => 'escrow_clearing', 'debit' => 1000.00, 'credit' => 0],
    ['code' => 'customer_payable', 'debit' => 0, 'credit' => 1000.00],
]);
$t('trial balance: balanced double entry', $tb['balanced'] && $eq($tb['total_debits'], 1000.00));
$t('trial balance: per-account net balances', $eq($tb['accounts']['escrow_clearing']['balance'], 1000.00));

$threw = false;
try {
    TrialBalance::assertBalanced([['code' => 'a', 'debit' => 10.00, 'credit' => 0], ['code' => 'b', 'debit' => 0, 'credit' => 9.99]]);
} catch (\RuntimeException $e) {
    $threw = true;
}
$t('trial balance: unbalanced entry THROWS', $threw);

$threw = false;
try {
    TrialBalance::assertBalanced([['code' => 'a', 'debit' => 10.00, 'credit' => 0], ['code' => 'b', 'debit' => 0, 'credit' => 10.00]]);
} catch (\RuntimeException $e) {
    $threw = true;
}
$t('trial balance: balanced entry does NOT throw', !$threw);

// --- Economics ----------------------------------------------------------
$c = Economics::contribution(1, 'C3', 480000.00, 96000.00, 36, 3);
$t('economics: holdback = 10% of fee', $eq($c['holdback'], 9600.00));
$t('economics: equalisation = 0.5% of fee', $eq($c['equalisation'], 480.00));
$t('economics: kicc_net = fee − holdback − equalisation', $eq($c['kicc_net'], 85920.00));
$t('economics: vendor_payout + fee == gmv', $eq($c['vendor_payout'] + 96000.00, 480000.00));
$t('economics: quality multiplier applied to fee', $eq(Economics::contribution(1, 'A1', 100, 10, 1, 1, 1.2)['kicc_net'], 10.74));

echo $fails === 0 ? "\nALL CORE ENGINE TESTS PASSED\n" : "\n{$fails} TEST(S) FAILED\n";
exit($fails === 0 ? 0 : 1);
