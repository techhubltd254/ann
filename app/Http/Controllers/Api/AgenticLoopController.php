<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AgenticLoopController extends Controller
{
    public function trigger(Request $request)
    {
        $request->validate([
            'action' => 'required|string|in:refresh_recommendations,clear_cache,update_seo',
        ]);

        $action = $request->input('action');
        $result = match ($action) {
            'refresh_recommendations' => $this->refreshRecommendations(),
            'clear_cache' => $this->clearCache(),
            'update_seo' => $this->updateSeo($request),
            default => ['status' => 'unknown_action'],
        };

        Log::info('Agentic action executed', [
            'action' => $action,
            'result' => $result,
        ]);

        return response()->json([
            'action' => $action,
            'result' => $result,
        ]);
    }

    public function status()
    {
        $seoPath = base_path('../agentic_loop/data/seo_instructions.json');
        $recPath = base_path('../agentic_loop/data/recommendation_weights.json');
        $pipelinePath = base_path('../agentic_loop/data/pipeline_params.json');

        return response()->json([
            'seo_instructions' => $this->readJson($seoPath),
            'recommendation_weights' => $this->readJson($recPath),
            'pipeline_params' => $this->readJson($pipelinePath),
            'cache_stats' => [
                'recommendations_cached' => Cache::has('destination_recommendations'),
                'counties_cached' => Cache::has('counties_all'),
            ],
        ]);
    }

    private function refreshRecommendations(): array
    {
        $recPath = base_path('../agentic_loop/data/recommendation_weights.json');
        $weights = $this->readJson($recPath);

        if ($weights && isset($weights['month'])) {
            Cache::put('seasonal_context', $weights, now()->addHours(6));
            return ['status' => 'recommendations_refreshed', 'month' => $weights['month']];
        }

        return ['status' => 'no_weights_found'];
    }

    private function clearCache(): array
    {
        Cache::flush();
        return ['status' => 'cache_cleared'];
    }

    private function updateSeo(Request $request): array
    {
        $keywords = $request->input('keywords', []);
        $seoPath = base_path('../agentic_loop/data/seo_instructions.json');
        $existing = $this->readJson($seoPath) ?? [];

        $existing['boost_keywords'] = $keywords;
        $existing['updated_at'] = now()->toIso8601String();

        file_put_contents($seoPath, json_encode($existing, JSON_PRETTY_PRINT));

        return ['status' => 'seo_updated', 'keywords' => $keywords];
    }

    private function readJson(string $path): ?array
    {
        if (!file_exists($path)) return null;
        $content = @file_get_contents($path);
        if (empty($content)) return null;
        return json_decode($content, true);
    }
}
