<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * SearchIntent — captures user search intent from Google referrals, UTM params,
 * geolocation, and landing page behavior. Persists to search_analytics for
 * admin reporting. Stores in session for marketplace personalization.
 */
class SearchIntent
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $response->isSuccessful() || ! $request->hasSession()) {
            return $response;
        }

        $session = $request->session();
        $referrer = $request->header('referer', $request->input('utm_source', ''));
        $searchEngine = preg_match('/google\.|bing\.|yahoo\.|duckduckgo\./', $referrer);

        $searchQuery = '';
        if ($searchEngine) {
            parse_str(parse_url($referrer, PHP_URL_QUERY) ?: '', $refParams);
            $searchQuery = $refParams['q'] ?? '';
        }
        if (empty($searchQuery)) {
            $searchQuery = $request->input('q', '');
        }

        $utm = [
            'source'   => $request->input('utm_source', 'direct'),
            'medium'   => $request->input('utm_medium', 'none'),
            'campaign' => $request->input('utm_campaign', ''),
            'term'     => $request->input('utm_term', $searchQuery),
        ];

        $country = $request->header('CF-IPCountry', '');
        $city = $request->header('CF-IPCity', '');

        $session->put('search_intent', [
            'query'    => $searchQuery,
            'referrer' => $referrer,
            'is_search' => (bool) $searchEngine,
            'engine'   => $searchEngine ? $this->detectEngine($referrer) : 'direct',
            'utm'      => $utm,
            'country'  => $country,
            'city'     => $city,
            'landing_url' => $request->fullUrl(),
            'landed_at'   => now()->toIso8601String(),
        ]);

        // Log to search_analytics once per session
        if ($searchQuery && ! $session->has('search_logged')) {
            $session->put('search_logged', true);
            try {
                DB::table('search_analytics')->insert([
                    'query'       => substr($searchQuery, 0, 200),
                    'engine'      => $searchEngine ? $this->detectEngine($referrer) : 'direct',
                    'source'      => $utm['source'],
                    'medium'      => $utm['medium'],
                    'campaign'    => substr($utm['campaign'] ?? '', 0, 100),
                    'country'     => $country,
                    'city'        => substr($city, 0, 100),
                    'landing_page' => substr($request->fullUrl(), 0, 500),
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            } catch (\Throwable) {}
        }

        if ($searchQuery) {
            $response->headers->set('X-Search-Intent', substr($searchQuery, 0, 200));
        }
        $response->headers->set('X-Referrer-Source', $utm['source']);

        return $response;
    }

    private function detectEngine(string $referrer): string
    {
        if (str_contains($referrer, 'google')) return 'google';
        if (str_contains($referrer, 'bing')) return 'bing';
        if (str_contains($referrer, 'yahoo')) return 'yahoo';
        if (str_contains($referrer, 'duckduckgo')) return 'duckduckgo';
        return 'other';
    }
}