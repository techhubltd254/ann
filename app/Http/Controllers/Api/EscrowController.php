<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EscrowTransaction;
use App\Services\EscrowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EscrowController extends Controller
{
    public function __construct(private EscrowService $escrow) {}

    public function sellerConfirm(int $id): JsonResponse
    {
        $escrow = EscrowTransaction::findOrFail($id);
        $this->authorize('update', $escrow);
        return response()->json($this->escrow->confirmBySeller($escrow));
    }

    public function buyerConfirm(int $id): JsonResponse
    {
        $escrow = EscrowTransaction::findOrFail($id);
        $this->authorize('update', $escrow);
        return response()->json($this->escrow->confirmByBuyer($escrow));
    }

    public function raiseDispute(Request $request, int $id): JsonResponse
    {
        $escrow = EscrowTransaction::findOrFail($id);
        $request->validate(['reason' => 'required|string|max:100', 'description' => 'required|string']);
        $dispute = $this->escrow->raiseDispute($escrow, auth()->id(), $request->reason, $request->description);
        $this->escrow->autoResolveLowValue($dispute);
        return response()->json($dispute->fresh());
    }

    public function tracking(int $id): JsonResponse
    {
        $escrow = EscrowTransaction::with('courierShipment.trackingEvents')->findOrFail($id);
        return response()->json($escrow->courierShipment);
    }

    public function releaseFunds(int $id): JsonResponse
    {
        $escrow = EscrowTransaction::findOrFail($id);
        $this->authorize('release', $escrow);
        return response()->json($this->escrow->releaseFunds($escrow));
    }
}
