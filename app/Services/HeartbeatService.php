<?php
namespace App\Services;

use App\Models\Booth;
use App\Models\BoothAuthorization;
use App\Models\HeartbeatLog;
use App\Models\ExhibitorStudioSession;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class HeartbeatService
{
    const HEARTBEAT_INTERVAL = 5; // seconds
    const TOKEN_VALIDITY = 10;    // seconds
    const MAX_MISSED_BEATS = 2;
    const GRACE_PERIOD = 10;      // seconds (2 missed beats)

    /**
     * Process a heartbeat ping from an exhibitor.
     * Returns an authorized token or null (which triggers slate).
     */
    public function processHeartbeat(int $boothId, string $sessionId, array $payload): ?array
    {
        $authorization = BoothAuthorization::where('booth_id', $boothId)
            ->where('status', 'AUTHORIZED')
            ->first();

        if (!$authorization) {
            $this->logHeartbeat($boothId, null, 'UNAUTHORIZED', $sessionId, $payload);
            return null;
        }

        // Generate signed token
        $token = $this->generateToken($authorization);

        $authorization->recordHeartbeat();

        $this->logHeartbeat($boothId, $authorization->id, 'AUTHORIZED', $sessionId, $payload, $token);

        return [
            'token' => $token,
            'valid_until' => now()->addSeconds(self::TOKEN_VALIDITY)->toIso8601String(),
            'authorized' => true,
        ];
    }

    /**
     * Generate a short-lived signed HMAC token.
     */
    public function generateToken(BoothAuthorization $authorization): string
    {
        $payload = [
            'booth_id' => $authorization->booth_id,
            'auth_id' => $authorization->id,
            'exp' => now()->addSeconds(self::TOKEN_VALIDITY)->timestamp,
            'seq' => mt_rand(),
        ];

        $payloadJson = json_encode($payload);
        $secret = config('app.key');
        $signature = hash_hmac('sha256', $payloadJson, $secret);

        return base64_encode($payloadJson) . '.' . $signature;
    }

    /**
     * Verify a heartbeat token. Returns the decoded payload or null.
     */
    public function verifyToken(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 2) return null;

        [$payloadB64, $signature] = $parts;
        $payloadJson = base64_decode($payloadB64);
        if (!$payloadJson) return null;

        $secret = config('app.key');
        $expectedSig = hash_hmac('sha256', $payloadJson, $secret);

        if (!hash_equals($expectedSig, $signature)) return null;

        $payload = json_decode($payloadJson, true);
        if (!$payload || !isset($payload['exp'])) return null;

        if ($payload['exp'] < now()->timestamp) return null;

        return $payload;
    }

    /**
     * Handle missed heartbeats — auto-pause stream if threshold reached.
     */
    public function handleMissedHeartbeat(BoothAuthorization $authorization): void
    {
        $authorization->incrementMissedHeartbeats();

        if ($authorization->missedHeartbeatThresholdReached()) {
            // Auto-pause: set stream to slate
            $authorization->booth->update(['stream_status' => 'paused']);

            // Terminate all active studio sessions for this booth
            ExhibitorStudioSession::where('booth_id', $authorization->booth_id)
                ->where('stream_status', 'live')
                ->update(['stream_status' => 'paused']);

            Log::warning("Heartbeat timeout for booth {$authorization->booth_id}: auto-paused", [
                'booth_id' => $authorization->booth_id,
                'missed_beats' => $authorization->missed_heartbeats,
            ]);
        }
    }

    /**
     * Terminate a booth — sever ingest, lock out exhibitor.
     */
    public function terminateBooth(BoothAuthorization $authorization, string $reasonCode, ?string $note = null): void
    {
        $authorization->terminate($reasonCode, $note);

        // Sever all studio sessions
        ExhibitorStudioSession::where('booth_authorization_id', $authorization->id)
            ->update(['stream_status' => 'terminated']);

        $authorization->booth->update(['stream_status' => 'offline']);

        Log::info("Booth {$authorization->booth_id} terminated: {$reasonCode}", [
            'by_user_id' => auth()->id(),
            'reason' => $reasonCode,
            'note' => $note,
        ]);
    }

    /**
     * Get heartbeat health status for a booth.
     * Returns: 'healthy' (green), 'warning' (amber), 'dead' (red), 'inactive'
     */
    public function getHeartbeatHealth(int $boothId): string
    {
        $lastHeartbeat = HeartbeatLog::where('booth_id', $boothId)
            ->where('status', 'AUTHORIZED')
            ->latest()
            ->first();

        if (!$lastHeartbeat) return 'inactive';

        $secondsSince = $lastHeartbeat->heartbeat_at->diffInSeconds(now());

        if ($secondsSince <= self::HEARTBEAT_INTERVAL * 3) return 'healthy';
        if ($secondsSince <= self::HEARTBEAT_INTERVAL * 6) return 'warning';

        return 'dead';
    }

    /**
     * Log a heartbeat event to the immutable audit trail.
     */
    private function logHeartbeat(int $boothId, ?int $authId, string $status, string $sessionId, array $payload, ?string $token = null): void
    {
        HeartbeatLog::create([
            'booth_id' => $boothId,
            'booth_authorization_id' => $authId,
            'status' => $status,
            'session_id' => $sessionId,
            'payload' => $payload,
            'token_sent' => $token,
            'token_verified' => $authId ? 'pass' : 'fail',
            'heartbeat_at' => now(),
        ]);
    }

    /**
     * Generate a per-booth-scoped API key.
     */
    public function generateApiKey(BoothAuthorization $authorization, int $expiresInDays = 365): string
    {
        $key = 'bk_' . Str::random(48);

        $authorization->update([
            'api_key' => hash('sha256', $key),
            'api_key_expires_at' => now()->addDays($expiresInDays),
        ]);

        return $key; // Return unhashed key once — exhibitor must save it
    }

    /**
     * Verify a booth API key (for session start authentication).
     */
    public function verifyApiKey(string $key): ?BoothAuthorization
    {
        $hash = hash('sha256', $key);

        return BoothAuthorization::where('api_key', $hash)
            ->where('status', 'AUTHORIZED')
            ->where('api_key_expires_at', '>', now())
            ->first();
    }

    /**
     * Get capacity metrics for the heartbeat broker.
     */
    public function getBrokerMetrics(): array
    {
        $totalAuthorized = BoothAuthorization::where('status', 'AUTHORIZED')->count();
        $liveSessions = ExhibitorStudioSession::where('stream_status', 'live')->count();
        $activeHeartbeats = HeartbeatLog::where('heartbeat_at', '>=', now()->subMinutes(1))->count();

        return [
            'total_authorized' => $totalAuthorized,
            'live_sessions' => $liveSessions,
            'active_heartbeats_per_minute' => $activeHeartbeats,
            'capacity_used_percent' => $totalAuthorized > 0
                ? round(($activeHeartbeats / ($totalAuthorized * 12)) * 100, 1) : 0,
        ];
    }
}