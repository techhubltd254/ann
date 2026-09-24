<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * SearchIntent — captures user search intent from Google referrals, UTM params,
 * geolocation, and landing page behavior. Stores in session for marketplace
 * personalization and SEO-driven product ranking.
 *
 * This middleware is what makes the marketplace "recommend" products based on
 * what the user was searching for on Google that brought them here.
 */
class SearchIntent
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $response->isSuccessful()) {
            return $response;
        }

        // Session may not be available in all contexts (tests, API, etc.)
        if (! $request->hasSession()) {
            return $response;
        }

        $session = $request->session();

        // 1. Capture referrer — was this user referred by Google?
        $referrer = $request->header('referer', $request->input('utm_source', ''));
        $searchEngine = preg_match('/google\.|bing\.|yahoo\.|duckduckgo\./', $referrer);

        // 2. Extract search query from Google referrer URL
        $searchQuery = '';
        if ($searchEngine) {
            parse_str(parse_url($referrer, PHP_URL_QUERY), $refParams);
            $searchQuery = $refParams['q'] ?? '';
        }
        // 3. Also check direct "q" param in the current URL
        if (empty($searchQuery)) {
            $searchQuery = $request->input('q', '');
        }

        // 4. Extract UTM params for campaign tracking
        $utm = [
            'source'   => $request->input('utm_source', 'direct'),
            'medium'   => $request->input('utm_medium', 'none'),
            'campaign' => $request->input('utm_campaign', ''),
            'term'     => $request->input('utm_term', $searchQuery),
            'content'  => $request->input('utm_content', ''),
        ];

        // 5. Get geo-location from Cloudflare headers
        $country = $request->header('CF-IPCountry', '');
        $city = $request->header('CF-IPCity', '');
        $region = $request->header('CF-Region', '');

        // 6. Store in session for the marketplace to use
        $session->put('search_intent', [
            'query'        => $searchQuery,
            'referrer'     => $referrer,
            'is_search'    => $searchEngine,
            'engine'       => $searchEngine ? $this->detectEngine($referrer) : 'direct',
            'utm'          => $utm,
            'country'      => $country,
            'city'         => $city,
            'region'       => $region,
            'landing_url'  => $request->fullUrl(),
            'landed_at'    => now()->toIso8601String(),
        ]);

        // 7. Set response headers so Cloudflare workers can read search intent
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