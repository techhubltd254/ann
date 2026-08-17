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

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'seller_id' => 'required|integer|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
            'reference_type' => 'required|string|max:30',
            'reference_id' => 'required|integer',
        ]);
        $escrow = $this->escrow->createEscrow(auth()->id(), $data['seller_id'], $data['amount'], $data['reference_type'], $data['reference_id']);
        return response()->json($escrow->fresh(), 201);
    }

    public function hold(int $id): JsonResponse
    {
        $escrow = EscrowTransaction::findOrFail($id);
        return response()->json($this->escrow->holdFunds($escrow));
    }

    public function sellerConfirm(int $id): JsonResponse
    {
        $escrow = EscrowTransaction::findOrFail($id);
        return response()->json($this->escrow->confirmBySeller($escrow));
    }

    public function ship(Request $request, int $id): JsonResponse
    {
        $escrow = EscrowTransaction::findOrFail($id);
        $data = $request->validate([
            'courier_name' => 'required|string|max:100',
            'tracking_number' => 'required|string|max:100',
            'origin' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
        ]);
        $shipment = $this->escrow->createShipment($escrow, $data['courier_name'], $data['tracking_number'], $data['origin'], $data['destination']);
        return response()->json($shipment->load('trackingEvents'), 201);
    }

    public function delivered(Request $request, int $id): JsonResponse
    {
        $escrow = EscrowTransaction::findOrFail($id);
        $data = $request->validate(['location' => 'required|string|max:255']);
        return response()->json($this->escrow->markDelivered($escrow, $data['location']));
    }

    public function buyerConfirm(int $id): JsonResponse
    {
        $escrow = EscrowTransaction::findOrFail($id);
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
        $shipment = $escrow->courierShipment;
        if (!$shipment) {
            return response()->json(['escrow_id' => $escrow->escrow_id, 'status' => $escrow->status, 'message' => 'No shipment created yet']);
        }
        return response()->json($shipment->load('trackingEvents'));
    }

    public function releaseFunds(int $id): JsonResponse
    {
        $escrow = EscrowTransaction::findOrFail($id);
        return response()->json($this->escrow->releaseFunds($escrow));
    }
}