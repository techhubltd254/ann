<?php
namespace App\Support;
use App\Models\MediaAsset;

class LiveExperienceMedia
{
    /** Image assignments are resolved by canonical owner identity, never card order. */
    public static function assignedImage($record): ?MediaAsset
    {
        return MediaAsset::where('owner_type', get_class($record))->where('owner_id', $record->id)
            ->where('kind', 'image')->where('status', 'ready')
            ->whereIn('slot', ['hero_image', 'fallback_image'])->with('derivatives')->latest('id')->first();
    }

    public static function resolve($record, string $type): array
    {
        $video = null;
        $poster = null;
        $illustrative = false;
        try {
            $owner = get_class($record);
            $asset = MediaAsset::where('owner_type', $owner)->where('owner_id', $record->id)
                ->where('status', 'ready')->whereIn('slot', ['hero_video', 'cover_video'])
                ->with('derivatives')->latest('id')->first();
            $video = $asset?->mp4Url();
            $poster = $asset?->posterUrl();
            $image = self::assignedImage($record);
            if ($image) {
                $poster = $image->thumbnailUrl() ?? $image->url();
                $illustrative = (bool) ($image->metadata['illustrative'] ?? str_contains($image->path, '/image/fallback-'));
            }
            if ($type === 'products') {
                $video ??= $record->video_url;
                if (!$video) foreach (($record->videos ?? []) as $v) {
                    $url = is_string($v) ? $v : ($v['url'] ?? null);
                    if ($url) { $video = $url; break; }
                }
                $poster ??= $record->images->first()?->url ?? $record->variants->first()?->image_url;
            }
            if ($type === 'counties') {
                $hero = MediaMapping::countyHero($record);
                $video = $hero['video'];
                $poster ??= MediaMapping::countyFallbackImage($record);
            }
            if ($type === 'institutions') $video = MediaMapping::institutionHero($record)['video'];
            $poster ??= $record->cover_image ?? ($type === 'institutions' ? $record->cover_image_url : null);
            foreach (['video', 'poster'] as $field) {
                $url = $$field;
                if ($url && !preg_match('#^(https?://|data:)#i', $url)) $$field = url('/media/video/' . ltrim($url, '/'));
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Experience media lookup failed', ['type' => $type, 'owner_id' => $record->id, 'error' => $e->getMessage()]);
        }
        return ['video' => $video, 'poster' => $poster, 'illustrative' => $illustrative];
    }
}
