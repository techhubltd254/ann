<?php

namespace App\Kicc\Services;

use App\Kicc\Support\TrialBalance;
use Illuminate\Support\Facades\DB;

/**
 * General Ledger reporting engine: trial balance, account balances,
 * period close (snapshot into gl_periods).
 */
class GLReportingService
{
    /**
     * Full trial balance across all accounts (optionally date-bounded).
     * @return array{accounts: array, total_debits: float, total_credits: float, balanced: bool}
     */
    public function trialBalance(?string $from = null, ?string $to = null): array
    {
        $rows = DB::table('gl_journal_lines as l')
            ->join('gl_journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->join('gl_accounts as a', 'a.id', '=', 'l.gl_account_id')
            ->when($from, fn ($q) => $q->whereDate('e.posted_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('e.posted_at', '<=', $to))
            ->groupBy('a.code', 'a.name', 'a.type')
            ->orderBy('a.code')
            ->get([
                'a.code', 'a.name', 'a.type',
                DB::raw('SUM(l.debit) as debits'),
                DB::raw('SUM(l.credit) as credits'),
            ]);

        $lines = $rows->map(fn ($r) => [
            'code' => $r->code,
            'debit' => (float) $r->debits,
            'credit' => (float) $r->credits,
        ])->all();

        $tb = TrialBalance::rollup($lines);
        foreach ($rows as $r) {
            $tb['accounts'][$r->code]['name'] = $r->name;
            $tb['accounts'][$r->code]['type'] = $r->type;
        }
        return $tb;
    }

    /** @throws \RuntimeException if the whole ledger is out of balance */
    public function assertLedgerBalanced(): void
    {
        TrialBalance::assertBalanced(
            DB::table('gl_journal_lines')
                ->get(['gl_account_id', 'debit', 'credit'])
                ->map(fn ($l) => ['code' => (string) $l->gl_account_id,
                                  'debit' => (float) $l->debit,
                                  'credit' => (float) $l->credit])
                ->all()
        );
    }

    /** Net balance of one account code (debit-positive). */
    public function accountBalance(string $code): float
    {
        $acc = DB::table('gl_accounts')->where('code', $code)->first();
        if (!$acc) return 0.0;
        $sums = DB::table('gl_journal_lines')
            ->where('gl_account_id', $acc->id)
            ->selectRaw('COALESCE(SUM(debit),0) AS d, COALESCE(SUM(credit),0) AS c')
            ->first();
        return round((float) $sums->d - (float) $sums->c, 2);
    }

    /**
     * Close a period: verify the date-bounded ledger balances, snapshot totals.
     * Idempotent — re-closing the same period updates the snapshot in place.
     */
    public function closePeriod(string $periodStart, string $periodEnd): array
    {
        $tb = $this->trialBalance($periodStart, $periodEnd);
        if (!$tb['balanced']) {
            throw new \RuntimeException("Cannot close period $periodStart..$periodEnd: ledger unbalanced");
        }
        DB::table('gl_periods')->updateOrInsert(
            ['period_start' => $periodStart],
            [
                'period_end' => $periodEnd,
                'total_debits' => $tb['total_debits'],
                'total_credits' => $tb['total_credits'],
                'status' => 'closed',
                'closed_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
        return ['period_start' => $periodStart, 'period_end' => $periodEnd] + $tb;
    }
}
