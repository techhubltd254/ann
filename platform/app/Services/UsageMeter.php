<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Usage metering for subscription features (max_booths, analytics, API calls).
 * Records into usage_logs against the subscriber's active subscription.
 */
class UsageMeter
{
    public static function record(int $userId, string $featureCode, int $quantity = 1, array $metadata = []): void
    {
        $subId = DB::table('user_subscriptions')
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->value('id');

        DB::table('usage_logs')->insert([
            'user_id' => $userId,
            'subscription_id' => $subId,
            'feature_code' => $featureCode,
            'quantity' => $quantity,
            'unit' => $metadata['unit'] ?? 'count',
            'metadata' => json_encode($metadata),
            'recorded_at' => now(),
            'created_at' => now(),
        ]);
    }

    /** Current-cycle usage total for a feature (for limit enforcement). */
    public static function usage(int $userId, string $featureCode): int
    {
        return (int) DB::table('usage_logs')
            ->where('user_id', $userId)
            ->where('feature_code', $featureCode)
            ->where('recorded_at', '>=', now()->startOfMonth())
            ->sum('quantity');
    }

    /** Enforce a plan limit, e.g. UsageMeter::allows($userId, 'booths', 20). */
    public static function withinLimit(int $userId, string $featureCode, int $limit): bool
    {
        return self::usage($userId, $featureCode) < $limit;
    }
}
