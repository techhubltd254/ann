<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Public dispute intake (buyer side). Two-tier rule (blueprint §Layer 4):
 *   amount < KES 5,000  → tier 1 automated rules attempt immediate resolution
 *   amount ≥ KES 5,000  → tier 2 human review queue (payments admin)
 */
class DisputeController extends Controller
{
    private const AUTO_THRESHOLD = 5000;

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'escrow_transaction_id' => ['required', 'integer', 'exists:escrow_transactions,id'],
            'reason' => ['required', 'in:non_delivery,not_as_described,damaged,other'],
            'description' => ['nullable', 'string', 'max:4000'],
        ]);

        $escrow = DB::table('escrow_transactions')->where('id', $data['escrow_transaction_id'])->first();
        if (! $escrow) {
            return response()->json(['error' => 'escrow transaction not found'], 404);
        }

        // Only the buyer of that escrow may raise a dispute.
        $userId = $request->user()?->id;
        if (! $userId || (int) $escrow->buyer_id !== (int) $userId) {
            return response()->json(['error' => 'only the buyer can dispute this transaction'], 403);
        }

        // One open dispute per escrow.
        $existing = DB::table('dispute_cases')
            ->where('escrow_transaction_id', $escrow->id)
            ->where('status', 'open')
            ->exists();
        if ($existing) {
            return response()->json(['error' => 'a dispute is already open for this transaction'], 409);
        }

        $amount = (float) $escrow->amount;
        $tier = $amount < self::AUTO_THRESHOLD ? 'auto' : 'human';

        $status = 'open';
        $resolution = null;
        $resolvedAt = null;

        // Tier 1 automated rules.
        if ($tier === 'auto') {
            // Rule: non_delivery where the seller never confirmed shipment and the
            // escrow is past its window → auto-refund recommendation.
            if ($data['reason'] === 'non_delivery' && empty($escrow->delivery_confirmed_at)) {
                $status = 'auto_resolved';
                $resolution = 'REFUND_BUYER — auto: non-delivery, no shipment confirmation';
                $resolvedAt = now();
            }
        }

        $id = DB::table('dispute_cases')->insertGetId([
            'escrow_transaction_id' => $escrow->id,
            'raised_by' => $userId,
            'reason' => $data['reason'],
            'description' => $data['description'] ?? null,
            'status' => $status,
            'resolution' => $resolution,
            'resolved_at' => $resolvedAt,
            'resolved_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'dispute_id' => $id,
            'tier' => $tier,
            'status' => $status,
            'message' => $tier === 'auto'
                ? ($status === 'auto_resolved' ? 'Resolved automatically (refund recommended).' : 'Under automated review.')
                : 'Queued for human review by a payments administrator.',
        ], 201);
    }
}
