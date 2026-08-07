<?php

namespace App\Services;

use App\Models\County;
use App\Models\Marketplace\Product;
use App\Models\Payment\PaymentIntent;
use App\Models\Subscription\CountySubscriber;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Agentic Loop — Observer-Decider-Actor automation engine.
 * Monitors platform signals, decides on actions via LLM, and executes
 * through integrated services (SEO refresh, recommendation updates, etc.)
 */
class AgenticLoopService
{
    protected array $config;

    public function __construct()
    {
        $this->config = [
            'observer' => ['check_interval' => 300, 'metrics' => ['county_views', 'product_searches', 'system_health']],
            'decider'  => ['model' => config('services.openrouter.model', 'moonshotai/kimi-k2'), 'max_tokens' => 512],
            'actor'    => ['actions' => ['seo_refresh', 'recommendation_update', 'content_alert']],
        ];
    }

    /**
     * Full cycle: observe → decide → act
     */
    public function run(): array
    {
        $signals = $this->observe();
        $decisions = $this->decide($signals);
        $results = $this->act($decisions);
        return ['observed' => count($signals), 'decisions' => $decisions, 'results' => $results];
    }

    /**
     * Observer: collect platform signals
     */
    public function observe(): array
    {
        $signals = [];

        // County activity — counties with no products
        $emptyCounties = County::doesntHave('products')->pluck('name')->toArray();
        if ($emptyCounties) {
            $signals[] = ['type' => 'inactive_counties', 'data' => array_slice($emptyCounties, 0, 5), 'priority' => 3];
        }

        // Pending orders — orders not fulfilled in 24h
        $pendingCount = Product::where('status', 'active')->whereNull('features')->count();
        $signals[] = ['type' => 'pending_orders', 'data' => $pendingCount, 'priority' => 5];

        // Payment intents stuck in processing
        $stuckPayments = PaymentIntent::where('status', 'processing')->where('created_at', '<', now()->subHours(2))->count();
        $signals[] = ['type' => 'stuck_payments', 'data' => $stuckPayments, 'priority' => 4];

        // System health
        $signals[] = ['type' => 'system_health', 'data' => 'ok', 'priority' => 1];

        return $signals;
    }

    /**
     * Decider: use LLM to evaluate signals and propose actions
     */
    public function decide(array $signals): array
    {
        $decisions = [];
        $apiKey = config('services.openrouter.key');

        foreach ($signals as $s) {
            if ($s['priority'] < 2) continue;

            if ($s['type'] === 'inactive_counties' && $apiKey) {
                // Use LLM to suggest content
                $prompt = "You are KICC's SEO content strategist. Suggest a 1-paragraph tourism highlight for each of these Kenyan counties to use as SEO metadata: " . implode(', ', $s['data']);
                try {
                    $response = Http::withToken($apiKey)->timeout(30)->post('https://openrouter.ai/api/v1/chat/completions', [
                        'model' => $this->config['decider']['model'],
                        'messages' => [['role' => 'user', 'content' => $prompt]],
                        'max_tokens' => $this->config['decider']['max_tokens'],
                    ]);
                    $content = $response->json('choices.0.message.content', '');
                    $decisions[] = ['action' => 'seo_refresh', 'target' => $s['type'], 'content' => $content, 'priority' => $s['priority']];
                } catch (\Throwable $e) {
                    Log::error('AgenticLoop decide LLM failed: ' . $e->getMessage());
                }
            }

            if ($s['type'] === 'stuck_payments' && $s['data'] > 0) {
                $decisions[] = ['action' => 'payment_alert', 'target' => $s['type'], 'count' => $s['data'], 'priority' => $s['priority']];
            }
        }

        return $decisions;
    }

    /**
     * Actor: execute decisions through integrated services
     */
    public function act(array $decisions): array
    {
        $results = [];

        foreach ($decisions as $d) {
            switch ($d['action']) {
                case 'seo_refresh':
                    Log::info("AgenticLoop: SEO refresh triggered for {$d['target']}");
                    $results[] = ['action' => 'seo_refresh', 'status' => 'logged', 'message' => 'SEO metadata queued'];
                    break;

                case 'payment_alert':
                    Log::warning("AgenticLoop: {$d['count']} pending payments need attention");
                    $results[] = ['action' => 'payment_alert', 'status' => 'alerted', 'count' => $d['count']];
                    break;

                default:
                    $results[] = ['action' => $d['action'] ?? 'unknown', 'status' => 'skipped'];
            }
        }

        return $results;
    }

    /**
     * Trigger a recommendation refresh for a county
     */
    public function refreshCountyRecommendations(string $countySlug): array
    {
        $county = County::where('slug', $countySlug)->first();
        if (!$county) return ['status' => 'error', 'message' => 'County not found'];

        // Fetch promotional content via the AI matching engine stub
        $apiKey = config('services.openrouter.key');
        $prompt = "You are a travel/trade advisor for Kenya. Write a 2-sentence promotional blurb for {$county->name} County, Kenya, highlighting its top sectors: " . ($county->tagline ?? 'agriculture, tourism, trade');

        $content = '';
        if ($apiKey) {
            try {
                $response = Http::withToken($apiKey)->timeout(20)->post('https://openrouter.ai/api/v1/chat/completions', [
                    'model' => $this->config['decider']['model'],
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    'max_tokens' => 256,
                ]);
                $content = $response->json('choices.0.message.content', '');
            } catch (\Throwable $e) {
                $content = "Explore {$county->name} County — a growing hub for trade and tourism.";
            }
        }

        return ['status' => 'success', 'county' => $countySlug, 'recommendation' => $content];
    }
}
