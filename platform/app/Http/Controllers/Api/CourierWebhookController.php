<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Courier tracking webhook — HMAC-verified, replay-safe, idempotent.
 * Receives partner status updates and records them against shipments.
 */
class CourierWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $secret = (string) config('services.courier.webhook_secret');
        $sig = (string) $request->header('X-Courier-Signature', '');
        $ts = (int) $request->header('X-Courier-Timestamp', 0);

        if ($secret === '' || abs(time() - $ts) > 300) {
            return response()->json(['error' => 'unauthorized'], 401);
        }
        $expected = hash_hmac('sha256', "$ts." . $request->getContent(), $secret);
        if (! hash_equals($expected, preg_replace('/^sha256=/', '', $sig))) {
            Log::warning('courier webhook: bad signature', ['ip' => $request->ip()]);
            return response()->json(['error' => 'bad signature'], 401);
        }

        $data = $request->validate([
            'tracking_no' => ['required', 'string'],
            'status' => ['required', 'string'],
            'event_id' => ['required', 'string'],
            'location' => ['nullable', 'string'],
            'occurred_at' => ['nullable', 'date'],
        ]);

        // idempotent on event_id
        if (DB::table('courier_tracking_events')->where('description', 'like', '%"event_id":"' . $data['event_id'] . '"%')->exists()) {
            return response()->json(['status' => 'duplicate']);
        }

        $shipmentId = DB::table('courier_shipments')->where('tracking_no', $data['tracking_no'])->value('id');

        DB::table('courier_tracking_events')->insert([
            'courier_shipment_id' => $shipmentId,
            'status' => $data['status'],
            'location' => $data['location'] ?? null,
            'description' => json_encode(['event_id' => $data['event_id'], 'note' => $data['description'] ?? null]),
            'occurred_at' => $data['occurred_at'] ?? now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // advance the shipment status
        if ($shipmentId) {
            DB::table('courier_shipments')->where('id', $shipmentId)->update([
                'status' => $data['status'],
                'updated_at' => now(),
            ]);
        }

        return response()->json(['status' => 'ok']);
    }
}
