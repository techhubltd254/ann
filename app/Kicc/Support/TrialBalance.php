<?php

namespace App\Kicc\Support;

/**
 * Trial-balance rollup over journal lines (pure — no DB).
 * Invariant enforced everywhere: total debits === total credits.
 */
class TrialBalance
{
    /** @param array[] $lines each: ['code'=>string,'debit'=>float,'credit'=>float] */
    public static function rollup(array $lines): array
    {
        $accounts = [];
        $debits = 0.0; $credits = 0.0;
        foreach ($lines as $l) {
            $code = $l['code'];
            $accounts[$code] ??= ['debit' => 0.0, 'credit' => 0.0];
            $accounts[$code]['debit'] += (float) ($l['debit'] ?? 0);
            $accounts[$code]['credit'] += (float) ($l['credit'] ?? 0);
            $debits += (float) ($l['debit'] ?? 0);
            $credits += (float) ($l['credit'] ?? 0);
        }
        foreach ($accounts as $code => $a) {
            $accounts[$code]['balance'] = round($a['debit'] - $a['credit'], 2);
        }
        return [
            'accounts' => $accounts,
            'total_debits' => round($debits, 2),
            'total_credits' => round($credits, 2),
            'balanced' => abs(round($debits - $credits, 2)) < 0.001,
        ];
    }

    /** @throws \RuntimeException when debits !== credits */
    public static function assertBalanced(array $lines): void
    {
        $t = self::rollup($lines);
        if (!$t['balanced']) {
            throw new \RuntimeException(sprintf(
                'Trial balance out of order: debits=%.2f credits=%.2f diff=%.2f',
                $t['total_debits'], $t['total_credits'], $t['total_debits'] - $t['total_credits']
            ));
        }
    }
}
