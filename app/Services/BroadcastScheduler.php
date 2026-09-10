<?php

namespace App\Services;

use App\Models\BroadcastSchedule;
use Illuminate\Support\Facades\DB;

/**
 * BroadcastScheduler — manages time-slot based content scheduling for screens.
 * Assigns media assets or live feeds to screens/groups with start/end times.
 */
class BroadcastScheduler
{
    public function scheduleForScreen(
        string $targetType,
        int $targetId,
        string $contentType,
        int $contentId,
        ?string $startsAt = null,
        ?string $endsAt = null,
        string $name = ''
    ): BroadcastSchedule {
        return BroadcastSchedule::create([
            'name' => $name ?: "Slot_" . now()->format('Hi'),
            'screen_target_type' => $targetType,
            'screen_target_id' => $targetId,
            'content_type' => $contentType,
            'media_asset_id' => $contentType === 'video' ? $contentId : null,
            'live_stream_id' => $contentType === 'live_feed' ? $contentId : null,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'is_active' => true,
        ]);
    }

    public function forScreen(int $screenId): array
    {
        return BroadcastSchedule::where('screen_target_type', 'screen')
            ->where('screen_target_id', $screenId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('starts_at')
            ->with(['mediaAsset', 'liveStream'])
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'content_type' => $s->content_type,
                'starts_at' => $s->starts_at?->toIso8601String(),
                'ends_at' => $s->ends_at?->toIso8601String(),
                'media_url' => $s->content_type === 'video'
                    ? ($s->mediaAsset?->mp4Url() ?? $s->mediaAsset?->url())
                    : null,
                'live_url' => $s->content_type === 'live_feed'
                    ? $s->liveStream?->hls_url
                    : null,
            ])->toArray();
    }
}