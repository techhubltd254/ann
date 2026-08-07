<?php

namespace KenyaSEI;

/**
 * Geo-Weather-Trend Detector
 * 
 * Real-time context detection: user location, weather, seasonal data.
 * This is the first layer of the context-aware SEO engine.
 */

class Detector
{
    private string $apiKey;
    private array $cache = [];

    public function __construct(string $weatherApiKey = '')
    {
        $this->apiKey = $weatherApiKey;
    }

    /**
     * Detect user context from IP address.
     * Returns: country, city, latitude, longitude, timezone
     */
    public function detectLocation(string $ip = ''): array
    {
        if (empty($ip)) {
            $ip = $this->getClientIP();
        }

        // Skip for local/internal IPs
        if ($this->isInternalIP($ip)) {
            return [
                'ip' => $ip,
                'country' => 'KE',
                'country_name' => 'Kenya',
                'city' => 'Nairobi',
                'latitude' => -1.2921,
                'longitude' => 36.8219,
                'timezone' => 'Africa/Nairobi',
                'source' => 'default',
            ];
        }

        // Try ip-api.com (free, no key needed)
        $data = $this->fetch("http://ip-api.com/json/{$ip}?fields=status,country,countryCode,city,lat,lon,timezone");
        
        if ($data && ($data['status'] ?? '') === 'success') {
            return [
                'ip' => $ip,
                'country' => $data['countryCode'],
                'country_name' => $data['country'],
                'city' => $data['city'],
                'latitude' => $data['lat'],
                'longitude' => $data['lon'],
                'timezone' => $data['timezone'],
                'source' => 'ip-api',
            ];
        }

        return [
            'ip' => $ip,
            'country' => 'KE',
            'country_name' => 'Kenya',
            'city' => 'Nairobi',
            'latitude' => -1.2921,
            'longitude' => 36.8219,
            'timezone' => 'Africa/Nairobi',
            'source' => 'fallback',
        ];
    }

    /**
     * Get current weather at a location.
     */
    public function detectWeather(float $lat, float $lon): array
    {
        if (empty($this->apiKey)) {
            return $this->estimateWeatherFromSeason($lat);
        }

        $data = $this->fetch(
            "https://api.openweathermap.org/data/2.5/weather?lat={$lat}&lon={$lon}&appid={$this->apiKey}&units=metric"
        );

        if ($data && isset($data['main']['temp'])) {
            $temp = $data['main']['temp'];
            return [
                'temperature_c' => $temp,
                'condition' => $data['weather'][0]['main'] ?? 'Unknown',
                'description' => $data['weather'][0]['description'] ?? '',
                'humidity' => $data['main']['humidity'] ?? 0,
                'wind_speed' => $data['wind']['speed'] ?? 0,
                'feels_like' => $data['main']['feels_like'] ?? $temp,
                'source' => 'openweathermap',
            ];
        }

        return $this->estimateWeatherFromSeason($lat);
    }

    /**
     * Classify weather into user-friendly tags for matching.
     */
    public function classifyUserWeather(float $temp): string
    {
        return match (true) {
            $temp <= 5 => 'freezing',
            $temp <= 12 => 'cold',
            $temp <= 18 => 'cool',
            $temp <= 25 => 'mild',
            $temp <= 30 => 'warm',
            $temp <= 35 => 'hot',
            default => 'very_hot',
        };
    }

    /**
     * Infer user intent from query text (basic keyword matching).
     * For deeper analysis, use IntentClassifier with Kimi K3.
     */
    public function inferIntent(string $query): string
    {
        $query = strtolower($query);

        $patterns = [
            'warm_escape' => ['summer vacation', 'warm', 'beach', 'sunny', 'tropical', 'escape winter', 'winter getaway'],
            'cool_retreat' => ['cool', 'cold', 'mountain', 'highlands', 'escape heat', 'chilly'],
            'adventure' => ['safari', 'adventure', 'wildlife', 'hiking', 'climbing', 'trekking'],
            'cultural' => ['culture', 'heritage', 'museum', 'history', 'traditional', 'festival'],
            'business' => ['conference', 'exhibition', 'trade', 'business', 'meeting', 'convention'],
            'family' => ['family', 'kids', 'children', 'holiday', 'break'],
            'luxury' => ['luxury', 'exclusive', 'premium', '5-star', 'resort', 'lodge'],
        ];

        foreach ($patterns as $intent => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($query, $kw)) {
                    return $intent;
                }
            }
        }

        return 'general';
    }

    /**
     * Estimate weather based on latitude/season when API unavailable.
     */
    private function estimateWeatherFromSeason(float $lat): array
    {
        $month = (int) date('n');
        $isNorthHemishere = $lat > 0;

        // Rough seasonal estimation
        $temp = match (true) {
            $month >= 3 && $month <= 5 => 22, // Spring / Long rains
            $month >= 6 && $month <= 8 => 20, // Summer / Cool dry
            $month >= 9 && $month <= 11 => 24, // Fall / Short rains
            default => 26, // Winter / Warm dry
        };

        // Adjust for latitude (warmer near equator)
        $temp += max(0, 12 - abs($lat));

        return [
            'temperature_c' => $temp,
            'condition' => 'Estimated',
            'description' => 'Seasonal estimate',
            'humidity' => 60,
            'wind_speed' => 10,
            'feels_like' => $temp,
            'source' => 'seasonal_estimate',
        ];
    }

    private function fetch(string $url): ?array
    {
        if (isset($this->cache[$url])) {
            return $this->cache[$url];
        }

        $ctx = stream_context_create([
            'http' => [
                'timeout' => 5,
                'user_agent' => 'KenyaSEI/1.0',
            ],
        ]);

        $result = @file_get_contents($url, false, $ctx);
        if ($result === false) {
            return null;
        }

        $data = json_decode($result, true);
        $this->cache[$url] = $data;
        return $data;
    }

    private function getClientIP(): string
    {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'] as $key) {
            $ip = $_SERVER[$key] ?? '';
            if (!empty($ip) && $ip !== '::1' && $ip !== '127.0.0.1') {
                return explode(',', $ip)[0];
            }
        }
        return '127.0.0.1';
    }

    private function isInternalIP(string $ip): bool
    {
        return in_array($ip, ['127.0.0.1', '::1', 'localhost'])
            || str_starts_with($ip, '192.168.')
            || str_starts_with($ip, '10.')
            || str_starts_with($ip, '172.');
    }
}
