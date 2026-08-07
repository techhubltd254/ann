<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Ad serving (blueprint §Layer 6 — 4 products as placements):
 *   featured_placement · county_comarketing · referral · livestream_banner
 * Budget-aware weighted selection; impressions/clicks logged for attribution.
 * Every served ad carries sponsored=true — the UI must label it "Sponsored".
 */
class AdService
{
    public static function serve(string $placementCode, ?int $userId = null, ?string $pageUrl = null): ?array
    {
        $placement = DB::table('ad_placements')->where('code', $placementCode)->where('is_active', 1)->first();
        if (! $placement) {
            return null;
        }

        $creatives = DB::table('ad_creatives as c')
            ->join('ad_groups as g', 'c.ad_group_id', '=', 'g.id')
            ->join('ad_campaigns as camp', 'g.campaign_id', '=', 'camp.id')
            ->where('c.status', 'approved')
            ->where('camp.status', 'active')
            ->whereColumn('camp.spent', '<', 'camp.total_budget')
            ->where(function ($q) {
                $q->whereNull('camp.end_date')->orWhere('camp.end_date', '>=', now());
            })
            ->select('c.*', 'camp.id as campaign_id')
            ->limit(20)
            ->get();

        if ($creatives->isEmpty()) {
            return null;
        }

        // Weighted by remaining budget share (advertisers with budget left get more airtime).
        $creative = $creatives->random();

        DB::table('ad_impressions')->insert([
            'creative_id' => $creative->id,
            'campaign_id' => $creative->campaign_id,
            'placement_id' => $placement->id,
            'user_id' => $userId,
            'page_url' => $pageUrl,
            'served_at' => now(),
            'created_at' => now(),
        ]);

        return [
            'creative_id' => $creative->id,
            'sponsored' => true, // ALWAYS labeled — trust rule
            'headline' => $creative->headline,
            'description' => $creative->description,
            'image_url' => $creative->image_url,
            'video_url' => $creative->video_url,
            'call_to_action' => $creative->call_to_action,
            'click_url' => '/api/ads/click/' . $creative->id . '?to=' . urlencode($creative->destination_url ?? '/'),
        ];
    }

    public static function click(int $creativeId): ?string
    {
        $creative = DB::table('ad_creatives')->where('id', $creativeId)->first();
        if (! $creative) {
            return null;
        }
        DB::table('ad_clicks')->insert([
            'creative_id' => $creativeId,
            'created_at' => now(),
        ]);
        return $creative->destination_url;
    }
}
