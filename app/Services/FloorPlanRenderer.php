<?php

namespace App\Services;

use App\Models\Booth;
use App\Models\FloorPlan;

/**
 * FloorPlanRenderer — generates interactive floor plan data for exhibitions.
 * Reads booth positions from layout_data JSON and enriches with booth details.
 */
class FloorPlanRenderer
{
    public function forPlan(FloorPlan $plan): array
    {
        $layout = $plan->layout_data ?? [];
        $boothIds = collect($layout['booths'] ?? [])->pluck('id');

        $booths = Booth::whereIn('id', $boothIds)
            ->where('status', 'available')
            ->get()
            ->keyBy('id');

        $enrichedBooths = [];
        foreach ($layout['booths'] ?? [] as $b) {
            $booth = $booths->get($b['id']);
            if (!$booth) continue;

            $enrichedBooths[] = [
                'id' => $booth->id,
                'booth_number' => $booth->booth_number,
                'name' => $booth->name,
                'size' => $booth->size,
                'x' => $b['x'] ?? 0,
                'y' => $b['y'] ?? 0,
                'width' => $b['w'] ?? ($b['width'] ?? 0),
                'height' => $b['h'] ?? ($b['height'] ?? 0),
                'rotation' => $b['rotation'] ?? 0,
                'is_available' => $booth->status === 'available',
                'price' => (float) ($booth->price ?? 0),
                'contact_name' => $booth->contact_name,
                'trader_type' => $booth->trader_type,
                'is_touch' => ($b['type'] ?? '') === 'touch_terminal',
            ];
        }

        return [
            'id' => $plan->id,
            'name' => $plan->name,
            'image_url' => $plan->image_url,
            'dimensions' => [
                'width' => $layout['width'] ?? 1200,
                'height' => $layout['height'] ?? 800,
            ],
            'booths' => $enrichedBooths,
            'total_booths' => count($enrichedBooths),
            'exhibition_id' => $plan->exhibition_id,
            'venue_id' => $plan->venue_id,
        ];
    }
}