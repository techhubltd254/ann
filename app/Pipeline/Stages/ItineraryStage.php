<?php

namespace App\Pipeline\Stages;

use App\Models\Itinerary;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ItineraryStage
{
    public function handle(array $context): array
    {
        $request = $context['request'];
        if (!$request || !$request->has('selections')) {
            $context['itinerary'] = null;
            return $context;
        }

        $selections = $request->input('selections', []);
        $days = min((int) $request->input('days', config('experience.itinerary.default_days', 3)), config('experience.itinerary.max_days', 14));
        $startDate = $request->input('start_date', now()->addDay()->toDateString());

        $context['selections'] = $selections;
        $context['itinerary'] = $this->generateItinerary($context, $selections, $days, $startDate);

        return $context;
    }

    protected function generateItinerary(array $context, array $selections, int $days, string $startDate): array
    {
        $items = $this->buildDayPlan($selections, $days);
        $totalEstimated = $this->calculateTotal($selections, $context);

        $itinerary = [
            'name' => $context['anchor_name'] . ' Experience',
            'start_date' => $startDate,
            'end_date' => date('Y-m-d', strtotime($startDate . " +{$days} days")),
            'days' => $days,
            'items_count' => count($selections),
            'total_estimated' => $totalEstimated,
            'day_plan' => $items,
            'currency' => 'KES',
            'status' => 'draft',
        ];

        // Save to travel_itineraries table if it exists
        try {
            $itineraryId = DB::table('travel_itineraries')->insertGetId([
                'user_id' => $context['request']?->user()?->id ?? 1,
                'name' => $itinerary['name'],
                'start_date' => $startDate,
                'end_date' => $itinerary['end_date'],
                'status' => 'draft',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($items as $day => $dayItems) {
                foreach ($dayItems as $item) {
                    DB::table('travel_itinerary_items')->insert([
                        'itinerary_id' => $itineraryId,
                        'item_type' => $item['type'] ?? 'attraction',
                        'item_id' => $item['id'] ?? 0,
                        'name' => $item['name'] ?? '',
                        'day_number' => $day,
                        'sort_order' => $item['order'] ?? 0,
                        'start_time' => $item['time'] ?? '09:00',
                        'notes' => $item['notes'] ?? '',
                        'estimated_cost' => $item['price'] ?? 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            $itinerary['id'] = $itineraryId;
        } catch (\Throwable $e) {
            // travel_itineraries table might not exist yet — that's ok
            $itinerary['id'] = null;
        }

        return $itinerary;
    }

    protected function buildDayPlan(array $selections, int $days): array
    {
        $plan = [];
        $itemsPerDay = max(1, (int) ceil(count($selections) / $days));
        $day = 1;
        $order = 0;

        foreach (array_chunk($selections, $itemsPerDay) as $chunk) {
            foreach ($chunk as $item) {
                $plan[$day][] = array_merge($item, ['order' => $order++, 'time' => $this->suggestTime($order)]);
            }
            $day++;
        }

        return $plan;
    }

    protected function suggestTime(int $order): string
    {
        $slots = ['09:00', '10:30', '12:00', '14:00', '15:30', '17:00', '18:30', '20:00'];
        return $slots[$order % count($slots)];
    }

    protected function calculateTotal(array $selections, array $context): float
    {
        $total = $context['estimated_total'] ?? 0;
        foreach ($selections as $item) {
            $total += (float) ($item['price'] ?? 0);
        }
        // Apply package discount
        $count = count($selections);
        foreach (config('experience.pricing.package_discounts', []) as $threshold => $percent) {
            if ($count >= $threshold) {
                $total *= (1 - $percent / 100);
                break;
            }
        }
        return round($total, 2);
    }
}