<?php

namespace App\Services;

use App\Models\EscrowTransaction;
use App\Models\DisputeCase;
use App\Models\CourierShipment;
use App\Models\CourierTrackingEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Escrow Service — 8-step flow:
 *  1. Deposit (buyer pays → funds held)
 *  2. Seller confirms order received
 *  3. Seller ships → courier tracking
 *  4. Buyer confirms delivery
 *  5. Funds released to seller
 *  6. Dispute (2-tier: auto → human)
 *  7. Resolution
 *  8. Release
 */
class EscrowService
{
    public function createEscrow(int $buyerId, int $sellerId, float $amount, string $referenceType, int $referenceId): EscrowTransaction
    {
        return EscrowTransaction::create([
            'buyer_id' => $buyerId,
            'seller_id' => $sellerId,
            'escrow_id' => 'ESC-' . strtoupper(Str::random(12)),
            'amount' => $amount,
            'currency' => 'KES',
            'status' => 'pending',
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'steps' => [
                ['step' => 'funds_held', 'label' => 'Buyer payment held in escrow', 'done' => false, 'at' => null],
                ['step' => 'seller_confirmed', 'label' => 'Seller confirmed order', 'done' => false, 'at' => null],
                ['step' => 'shipped', 'label' => 'Item shipped', 'done' => false, 'at' => null],
                ['step' => 'delivered', 'label' => 'Item delivered', 'done' => false, 'at' => null],
                ['step' => 'buyer_confirmed', 'label' => 'Buyer confirmed delivery', 'done' => false, 'at' => null],
                ['step' => 'released', 'label' => 'Funds released to seller', 'done' => false, 'at' => null],
            ],
            'current_step' => 0,
        ]);
    }

    public function holdFunds(EscrowTransaction $escrow): EscrowTransaction
    {
        $escrow->update(['status' => 'held', 'current_step' => 1]);
        $this->markStep($escrow, 'funds_held');
        Log::info('escrow: funds held', ['escrow_id' => $escrow->id, 'amount' => $escrow->amount]);
        return $escrow->fresh();
    }

    public function confirmBySeller(EscrowTransaction $escrow): EscrowTransaction
    {
        $escrow->update(['seller_confirmed_at' => now(), 'current_step' => 2]);
        $this->markStep($escrow, 'seller_confirmed');
        Log::info('escrow: seller confirmed', ['escrow_id' => $escrow->id]);
        return $escrow->fresh();
    }

    public function createShipment(EscrowTransaction $escrow, string $courierName, string $trackingNumber, string $origin, string $destination): CourierShipment
    {
        $shipment = CourierShipment::create([
            'escrow_transaction_id' => $escrow->id,
            'tracking_number' => $trackingNumber,
            'courier_name' => $courierName,
            'status' => 'shipped',
            'origin_address' => $origin,
            'destination_address' => $destination,
            'shipped_at' => now(),
            'estimated_delivery' => now()->addDays(3),
        ]);

        $shipment->trackingEvents()->create([
            'status' => 'picked_up',
            'location' => $origin,
            'description' => 'Package picked up by courier',
            'occurred_at' => now(),
        ]);

        $escrow->update(['current_step' => 3]);
        $this->markStep($escrow, 'shipped');
        Log::info('escrow: shipped', ['escrow_id' => $escrow->id, 'tracking' => $trackingNumber]);
        return $shipment;
    }

    public function markDelivered(EscrowTransaction $escrow, string $location): EscrowTransaction
    {
        // Mark delivery confirmed
        $escrow->update(['delivery_confirmed_at' => now(), 'current_step' => 4]);
        $this->markStep($escrow, 'delivered');

        // Update courier tracking
        $shipment = $escrow->courierShipment ?? CourierShipment::where('escrow_transaction_id', $escrow->id)->first();
        if ($shipment) {
            $shipment->update(['delivered_at' => now(), 'status' => 'delivered']);
            $shipment->trackingEvents()->create([
                'status' => 'delivered',
                'location' => $location,
                'description' => 'Package delivered successfully',
                'occurred_at' => now(),
            ]);
        }
        return $escrow->fresh();
    }

    public function confirmByBuyer(EscrowTransaction $escrow): EscrowTransaction
    {
        $escrow->update(['buyer_confirmed_at' => now(), 'current_step' => 5]);
        $this->markStep($escrow, 'buyer_confirmed');
        Log::info('escrow: buyer confirmed delivery', ['escrow_id' => $escrow->id]);
        return $escrow->fresh();
    }

    public function releaseFunds(EscrowTransaction $escrow): EscrowTransaction
    {
        $escrow->update([
            'status' => 'released',
            'released_at' => now(),
            'current_step' => 6,
        ]);
        $this->markStep($escrow, 'released');
        Log::info('escrow: funds released to seller', ['escrow_id' => $escrow->id, 'amount' => $escrow->amount, 'seller_id' => $escrow->seller_id]);
        return $escrow->fresh();
    }

    public function raiseDispute(EscrowTransaction $escrow, int $raisedBy, string $reason, string $description): DisputeCase
    {
        $dispute = DisputeCase::create([
            'escrow_transaction_id' => $escrow->id,
            'raised_by' => $raisedBy,
            'reason' => $reason,
            'description' => $description,
            'status' => 'open',
        ]);
        $escrow->update(['status' => 'disputed']);
        Log::warning('escrow: dispute raised', ['escrow_id' => $escrow->id, 'reason' => $reason]);
        return $dispute;
    }

    public function resolveDispute(DisputeCase $dispute, string $resolution, int $resolvedBy, ?string $winner = null): DisputeCase
    {
        $dispute->update([
            'status' => 'resolved',
            'resolution' => $resolution,
            'resolved_at' => now(),
            'resolved_by' => $resolvedBy,
        ]);

        $escrow = $dispute->escrowTransaction;
        if ($winner === 'buyer') {
            $this->releaseFunds($escrow);
        } elseif ($winner === 'seller') {
            $this->releaseFunds($escrow);
        }
        // If split or neither, funds stay in escrow until manual resolution
        return $dispute->fresh();
    }

    public function autoResolveLowValue(DisputeCase $dispute): bool
    {
        $escrow = $dispute->escrowTransaction;
        // Auto-resolve disputes under KES 1,000 in buyer's favor
        if ($escrow->amount < 1000) {
            $this->resolveDispute($dispute, 'Auto-resolved: low-value dispute refunded to buyer', 1, 'buyer');
            return true;
        }
        // Auto-resolve if seller has high trust score (A or B)
        $seller = User::find($escrow->seller_id);
        if ($seller && in_array($seller->trust_grade ?? 'C', ['A', 'B'])) {
            $this->resolveDispute($dispute, 'Auto-resolved: seller has high trust rating', 1, 'seller');
            return true;
        }
        return false;
    }

    protected function markStep(EscrowTransaction $escrow, string $stepName): void
    {
        $steps = $escrow->steps ?? [];
        foreach ($steps as &$s) {
            if ($s['step'] === $stepName) {
                $s['done'] = true;
                $s['at'] = now()->toIso8601String();
            }
        }
        $escrow->update(['steps' => $steps]);
    }
}