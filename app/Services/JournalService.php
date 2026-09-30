<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Double-entry journal (append-only). Every journal is balanced (sum debit == sum credit).
 * Renamed from LedgerService to avoid collision with App\Kicc\Services\LedgerService
 * which handles escrow lifecycle (hold/capture/release/refund).
 */
class JournalService
{
    /**
     * Post a balanced journal.
     * @param array{
     *   journal_ref: string,
     *   memo?: string|null,
     *   source?: string|null,
     *   entries: array<int, array{account: string, debit: float|int|string, credit: float|int|string, ref_type?: string|null, ref_id?: int|null}>
     * } $payload
     */
    public static function post(array $payload): int
    {
        abort_unless(!empty($payload['journal_ref']), 422, 'journal_ref required.');
        abort_unless(!empty($payload['entries']) && is_array($payload['entries']), 422, 'entries required.');

        $debit = 0.0;
        $credit = 0.0;
        foreach ($payload['entries'] as $e) {
            $debit  += (float) ($e['debit'] ?? 0);
            $credit += (float) ($e['credit'] ?? 0);
        }
        if (abs($debit - $credit) > 0.005) {
            throw new RuntimeException("Unbalanced journal '{$payload['journal_ref']}': debit {$debit} != credit {$credit}");
        }

        return DB::transaction(function () use ($payload) {
            $journalId = DB::table('ledger_journal')->insertGetId([
                'journal_ref' => $payload['journal_ref'],
                'memo'        => $payload['memo']        ?? null,
                'source'      => $payload['source']      ?? null,
                'posted_at'   => now(),
                'posted_by'   => auth()->id(),
                'total_debit' => self::sumAmounts($payload['entries'], 'debit'),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            foreach ($payload['entries'] as $e) {
                DB::table('ledger_entries')->insert([
                    'journal_id' => $journalId,
                    'account'    => (string) $e['account'],
                    'debit'      => (float) ($e['debit'] ?? 0),
                    'credit'     => (float) ($e['credit'] ?? 0),
                    'ref_type'   => $e['ref_type'] ?? null,
                    'ref_id'     => $e['ref_id']   ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            return $journalId;
        });
    }

    /** Verify a journal id is balanced; returns the journal total or throws. */
    public static function assertBalanced(int $journalId): float
    {
        $row = DB::table('ledger_entries')
            ->selectRaw('SUM(debit) as d, SUM(credit) as c')
            ->where('journal_id', $journalId)->first();
        if (!$row || abs((float) $row->d - (float) $row->c) > 0.005) {
            throw new RuntimeException("Journal {$journalId} is unbalanced.");
        }
        return (float) $row->d;
    }

    private static function sumAmounts(array $entries, string $key): float
    {
        $s = 0.0;
        foreach ($entries as $e) $s += (float) ($e[$key] ?? 0);
        return $s;
    }
}
