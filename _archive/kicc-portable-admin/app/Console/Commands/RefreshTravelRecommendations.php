<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Refreshes origin-market weather data for the travel recommendation
 * engine ("cold in London → recommend a Kenyan summer"). Run hourly:
 * fires an n8n webhook when a cold market is detected so marketing
 * automation can push the "escape the cold" campaign.
 */
class RefreshTravelRecommendations extends Command
{
    protected $signature = 'travel:refresh-recommendations {markets=London,Berlin,Toronto,Moscow}';
    protected $description = 'Refresh origin-market weather and trigger SEO travel recommendations';

    public function handle(): int
    {
        $markets = explode(',', $this->argument('markets'));
        foreach ($markets as $city) {
            $city = trim($city);
            try {
                $r = Http::timeout(5)->get("https://wttr.in/" . urlencode($city) . "?format=j1");
                if (!$r->ok()) continue;
                $cur = $r->json('current_condition.0');
                $temp = (int) ($cur['temp_C'] ?? 0);
                $data = ['city' => $city, 'temp' => $temp, 'desc' => $cur['weatherDesc'][0]['value'] ?? '', 'live' => true];
                Cache::put("weather:{$city}", $data, 3600);
                $this->info("{$city}: {$temp}°C ({$data['desc']})");

                if ($temp <= 14) {
                    \App\Services\N8nService::fire('cold_market_detected', [
                        'city' => $city, 'temp' => $temp,
                        'recommendation' => 'Push Kenya summer vacation SEO campaign (Mombasa 30°C)',
                    ]);
                }
            } catch (\Throwable $e) {
                $this->warn("{$city}: weather fetch failed — {$e->getMessage()}");
            }
        }
        return self::SUCCESS;
    }
}
