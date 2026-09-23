<?php

namespace App\Kicc\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * General Ledger service: double-entry + escrow lifecycle
 * (hold → capture → split → release | refund).
 * All primitives write both ledger_transactions and gl journal lines.
 */
class LedgerService
{
    public function hold(string $pipelineCode, float $amount, string $refType, int $refId, ?int $countyId = null, ?int $userId = null, array $meta = []): int
    {
        return DB::transaction(function () use ($pipelineCode, $amount, $refType, $refId, $countyId, $userId, $meta) {
            $txId = DB::table('ledger_transactions')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'pipeline_code' => $pipelineCode,
                'county_id' => $countyId,
                'user_id' => $userId,
                'type' => 'hold',
                'amount' => $amount,
                'status' => 'held',
                'reference_type' => $refType,
                'reference_id' => $refId,
                'meta' => json_encode($meta),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('ledger_holds')->insert([
                'ledger_transaction_id' => $txId,
                'amount' => $amount,
                'held_at' => now(),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $this->post($txId, 'Escrow hold: ' . $pipelineCode, [
                ['code' => 'escrow_clearing', 'debit' => $amount, 'credit' => 0],
                ['code' => 'customer_payable', 'debit' => 0, 'credit' => $amount],
            ]);
            return $txId;
        });
    }

    public function capture(int $txId): void
    {
        DB::transaction(function () use ($txId) {
            $tx = DB::table('ledger_transactions')->where('id', $txId)->lockForUpdate()->first();
            abort_if(!$tx, 404, 'Ledger transaction not found');
            abort_if($tx->status !== 'held', 422, 'Only held transactions can be captured');
            DB::table('ledger_transactions')->where('id', $txId)->update(['status' => 'settled', 'updated_at' => now()]);
            DB::table('ledger_holds')->where('ledger_transaction_id', $txId)->update(['released_at' => now()]);
            $this->post($txId, 'Capture: ' . $tx->pipeline_code, [
                ['code' => 'customer_payable', 'debit' => $tx->amount, 'credit' => 0],
                ['code' => 'merchant_payable', 'debit' => 0, 'credit' => $tx->amount],
            ]);
        });
    }

    public function split(int $txId, array $splits): void
    {
        DB::transaction(function () use ($txId, $splits) {
            foreach ($splits as $s) {
                DB::table('ledger_splits')->insert([
                    'ledger_transaction_id' => $txId,
                    'payee_type' => $s['payee_type'],
                    'payee_id' => $s['payee_id'],
                    'split_code' => $s['split_code'],
                    'amount' => $s['amount'],
                    'status' => 'pending',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        });
    }

    public function release(int $txId): void
    {
        DB::table('ledger_transactions')->where('id', $txId)->update(['status' => 'released', 'updated_at' => now()]);
        DB::table('ledger_holds')->where('ledger_transaction_id', $txId)->whereNull('released_at')->update(['released_at' => now()]);
    }

    public function refund(int $txId, string $reason = ''): void
    {
        DB::transaction(function () use ($txId, $reason) {
            $tx = DB::table('ledger_transactions')->where('id', $txId)->lockForUpdate()->first();
            abort_if(!$tx, 404, 'Ledger transaction not found');
            DB::table('ledger_transactions')->where('id', $txId)->update(['status' => 'refunded', 'updated_at' => now()]);
            $this->post($txId, 'Refund: ' . $tx->pipeline_code . ' ' . $reason, [
                ['code' => 'merchant_payable', 'debit' => $tx->amount, 'credit' => 0],
                ['code' => 'escrow_clearing', 'debit' => 0, 'credit' => $tx->amount],
            ]);
        });
    }

    /** Post a balanced double-entry journal. Throws if debits != credits. */
    public function post(int $txId, string $description, array $lines): int
    {
        $debit = array_sum(array_column($lines, 'debit'));
        $credit = array_sum(array_column($lines, 'credit'));
        if (round($debit - $credit, 2) !== 0.0) {
            throw new \RuntimeException('Unbalanced journal entry: debit=' . $debit . ' credit=' . $credit);
        }
        return DB::transaction(function () use ($txId, $description, $lines) {
            $entryId = DB::table('gl_journal_entries')->insertGetId([
                'entry_no' => 'JE-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
                'reference_type' => 'ledger_transaction',
                'reference_id' => $txId,
                'description' => $description,
                'posted_at' => now(),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach ($lines as $line) {
                $account = DB::table('gl_accounts')->where('code', $line['code'])->first();
                if (!$account) {
                    $accountId = DB::table('gl_accounts')->insertGetId([
                        'code' => $line['code'], 'name' => ucwords(str_replace('_', ' ', $line['code'])),
                        'type' => 'asset', 'currency' => 'KES', 'is_active' => true,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                } else {
                    $accountId = $account->id;
                }
                DB::table('gl_journal_lines')->insert([
                    'journal_entry_id' => $entryId, 'gl_account_id' => $accountId,
                    'debit' => $line['debit'], 'credit' => $line['credit'],
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            return $entryId;
        });
    }
}
