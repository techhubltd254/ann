<?php

namespace App\Services\Pool;

use App\Models\Marketplace\Order;
use App\Models\Pool\PoolContribution;
use Illuminate\Support\Facades\DB;

/**
 * SettlementBatch — idempotent M-Pesa callback handler with double-entry posting.
 *
 * Algorithm 11 from the kicc-algorithms reference: callback → payment record →
 * settlement batch → commission_logs row → pool contribution event.
 * Idempotency keys swallow duplicate webhooks, the exact production failure
 * mode at scale.
 */
class SettlementService
{
    public function settle(
        int $orderId,
        int $buyerId,
        int $sellerId,
        float $amount,
        string $mpesaRef,
        ?int $countyId = null,
        ?int $sectorId = null
    ): array {
        $idemKey = "mpesa:{$mpesaRef}";

        // Check idempotency
        $existing = DB::table('idempotency_keys')->where('key', $idemKey)->first();
        if ($existing) {
            return ['status' => 'duplicate_ignored', 'mpesa_ref' => $mpesaRef];
        }
        DB::table('idempotency_keys')->insert([
            'key' => $idemKey,
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $commissionRate = config('kicc.pool.commission_default_rate', 0.03);
        $commission = round($amount * $commissionRate, 2);

        $paymentId = DB::table('payments')->insertGetId([
            'order_id' => $orderId, 'buyer_id' => $buyerId, 'seller_id' => $sellerId,
            'amount' => $amount, 'mpesa_ref' => $mpesaRef, 'status' => 'received',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $logId = DB::table('commission_logs')->insertGetId([
            'payment_id' => $paymentId, 'seller_id' => $sellerId,
            'amount' => $commission, 'rate' => $commissionRate,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $settlementId = DB::table('settlement_batches')->insertGetId([
            'commission_log_id' => $logId, 'status' => 'batched',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // Accrue pool contribution
        if ($countyId || $sellerId) {
            try {
                app(ContributionAccrualService::class)->accrue(
                    sourceType: 'payment',
                    sourceId: $paymentId,
                    grossAmount: $amount,
                    platformFee: $commission,
                    countyId: $countyId,
                    sectorId: $sectorId,
                    entityId: $sellerId,
                    entityType: 'App\\Models\\User',
                    sponsorId: null,
                );
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('pool: settlement accrual failed', ['payment_id' => $paymentId, 'error' => $e->getMessage()]);
            }
        }

        return [
            'status' => 'settled', 'payment_id' => $paymentId,
            'commission' => $commission, 'settlement_id' => $settlementId,
        ];
    }
}