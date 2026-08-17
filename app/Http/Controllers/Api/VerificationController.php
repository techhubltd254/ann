<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\VerificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class VerificationController extends Controller
{
    public function __construct(private VerificationService $verification) {}

    public function submitKraPin(Request $request): JsonResponse
    {
        $request->validate([
            'kra_pin' => 'required|string|size:11|regex:/^[A-Z0-9]+$/',
            'document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);
        $result = $this->verification->verifyKraPin($request->user(), $request->kra_pin, $request->file('document'));
        return response()->json($result);
    }

    public function submitNationalId(Request $request): JsonResponse
    {
        $request->validate([
            'id_number' => 'required|string|size:8|regex:/^[0-9]+$/',
            'document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);
        $result = $this->verification->verifyNationalId($request->user(), $request->id_number, $request->file('document'));
        return response()->json($result);
    }

    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        return response()->json([
            'tier' => $user->verification_tier ?? 0,
            'status' => $user->verification_status ?? 'unverified',
            'kra_pin' => $user->kra_pin ? substr($user->kra_pin, 0, 3) . '***' : null,
            'id_number' => $user->id_number ? '***' . substr($user->id_number, -2) : null,
        ]);
    }
}
