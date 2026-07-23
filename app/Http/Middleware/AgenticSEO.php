<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class AgenticSEO
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!$response->isSuccessful()) {
            return $response;
        }

        $seoInstrPath = base_path('../agentic_loop/data/seo_instructions.json');
        $recWeightsPath = base_path('../agentic_loop/data/recommendation_weights.json');

        $seoInstr = $this->readJson($seoInstrPath);
        $recWeights = $this->readJson($recWeightsPath);

        if ($seoInstr && !empty($seoInstr['boost_keywords'])) {
            $keywords = implode(', ', $seoInstr['boost_keywords']);
            $response->header('X-SEO-Boost', $keywords);
        }

        if ($recWeights && isset($recWeights['month'])) {
            $response->header('X-Season-Context', $recWeights['season'] . '_' . $recWeights['month']);
        }

        return $response;
    }

    private function readJson(string $path): ?array
    {
        if (!file_exists($path)) return null;
        $content = @file_get_contents($path);
        if (empty($content)) return null;
        return json_decode($content, true);
    }
}
