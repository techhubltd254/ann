<?php

namespace KenyaSEI;

/**
 * Destination Matcher
 * 
 * Core algorithm: match user context (location, weather, intent)
 * to the best Kenyan county/destination in real time.
 */

class DestinationMatcher
{
    private array $counties;

    public function __construct()
    {
        $path = __DIR__ . '/../data/counties.json';
        $this->counties = json_decode(file_get_contents($path), true) ?? [];
    }

    /**
     * Find the best county matches for a user based on their context.
     *
     * @param array $userLocation  From Detector::detectLocation()
     * @param array $userWeather   From Detector::detectWeather()
     * @param string $intent       From Detector::inferIntent() or IntentClassifier
     * @param int $limit           Max results to return
     * @return array               Ranked list of counties with match scores
     */
    public function match(array $userLocation, array $userWeather, string $intent, int $limit = 5): array
    {
        $userTemp = $userWeather['temperature_c'] ?? 20;
        $userSeason = $this->getSeasonForTemp($userTemp);

        $scored = [];

        foreach ($this->counties as $county) {
            $score = $this->calculateMatchScore($county, $userTemp, $userSeason, $intent, $userLocation);
            if ($score > 0) {
                $scored[] = [
                    'county' => $county,
                    'score' => $score,
                    'reason' => $this->getMatchReason($county, $userTemp, $userSeason, $intent),
                ];
            }
        }

        usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($scored, 0, $limit);
    }

    /**
     * Calculate how well a county matches the user's context.
     * Score 0-100.
     */
    private function calculateMatchScore(
        array $county, 
        float $userTemp, 
        string $userSeason, 
        string $intent,
        array $userLocation
    ): int {
        $score = 50; // base

        // 1. Weather complementarity (core feature)
        $countyWarmest = $this->extractTemp($county['warmest_month'] ?? '30°C');
        $countyCoolest = $this->extractTemp($county['coolest_month'] ?? '20°C');
        $countyAvgTemp = ($countyWarmest + $countyCoolest) / 2;

        // If user is cold, recommend warm counties
        if ($userTemp <= 12 && $countyAvgTemp >= 28) {
            $score += 35;
        }
        // If user is hot, recommend cool counties
        elseif ($userTemp >= 30 && $countyAvgTemp <= 22) {
            $score += 35;
        }
        // If weathers match (similar temp), neutral
        elseif (abs($userTemp - $countyAvgTemp) <= 5) {
            $score += 10;
        }

        // 2. Intent match
        $score += $this->intentMatchBonus($county, $intent);

        // 3. Season alignment
        $score += $this->seasonMatchBonus($county, $userSeason);

        // 4. Distance bonus (same country = bonus)
        if (($userLocation['country'] ?? '') === 'KE') {
            $score += 5;
        }

        // 5. Tourism readiness (counties with tourism infrastructure rank higher)
        $isTourismCounty = in_array('Tourism', $county['primary_sectors']);
        if ($isTourismCounty) {
            $score += 10;
        }

        return min(100, max(0, $score));
    }

    private function intentMatchBonus(array $county, string $intent): int
    {
        $sectorMap = [
            'warm_escape' => ['Tourism'],
            'beach' => ['Tourism'],
            'cool_retreat' => ['Agriculture', 'Education'],
            'adventure' => ['Tourism', 'Conservation'],
            'cultural' => ['Tourism', 'Creative Economy'],
            'business' => ['Technology', 'Manufacturing', 'Finance'],
            'family' => ['Tourism', 'Education'],
            'luxury' => ['Tourism'],
            'general' => [],
        ];

        $targetSectors = $sectorMap[$intent] ?? [];
        if (empty($targetSectors)) return 0;

        foreach ($targetSectors as $sector) {
            if (in_array($sector, $county['primary_sectors'])) {
                return 20;
            }
        }

        return 5;
    }

    private function seasonMatchBonus(array $county, string $userSeason): int
    {
        $dryCounties = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 15, 23, 25, 30];
        
        if ($userSeason === 'wet' && in_array($county['id'], $dryCounties)) {
            return 15; // Recommend dry counties during wet season
        }
        
        return 5;
    }

    private function getMatchReason(array $county, float $userTemp, string $userSeason, string $intent): string
    {
        $reasons = [];

        if ($userTemp <= 12) {
            $reasons[] = "Escape the cold — {$county['name']} is warm";
        } elseif ($userTemp >= 30) {
            $reasons[] = "Cool retreat in {$county['name']}";
        }

        if (in_array('Tourism', $county['primary_sectors'])) {
            $highlights = array_slice($county['tourism_highlights'], 0, 2);
            $reasons[] = 'Visit: ' . implode(', ', $highlights);
        }

        return implode('. ', $reasons) ?: "Explore {$county['name']}";
    }

    private function extractTemp(string $str): float
    {
        preg_match('/(\d+)/', $str, $m);
        return isset($m[1]) ? (float) $m[1] : 25;
    }

    private function getSeasonForTemp(float $temp): string
    {
        if ($temp > 28) return 'hot';
        if ($temp > 20) return 'warm';
        if ($temp > 12) return 'cool';
        return 'cold';
    }
}
