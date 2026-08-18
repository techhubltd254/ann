<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Image search — upload an image, get matching Kenyan destinations.
 * Uses OpenRouter multimodal vision for scene recognition.
 */
class ImageSearchController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:10240',
        ]);

        $path = $request->file('image')->getPathname();
        $mime = $request->file('image')->getMimeType();
        $data = base64_encode(file_get_contents($path));

        $apiKey = config('services.openrouter.key');
        if (! $apiKey) {
            return response()->json(['error' => 'AI service not configured'], 503);
        }

        $response = Http::withToken($apiKey)
            ->timeout(30)
            ->post('https://openrouter.ai/api/v1/chat/completions', [
                'model' => 'openai/gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => 'You are a Kenyan tourism expert. Analyze this image and return a JSON object with: 1) "scene" (2-3 words describing the scene), 2) "counties" (array of 3-5 Kenyan county names that match this scene), 3) "attractions" (array of 3-5 specific attraction names in Kenya related to this scene). Return ONLY valid JSON.'],
                            ['type' => 'image_url', 'image_url' => ['url' => "data:$mime;base64,$data"]],
                        ],
                    ],
                ],
                'max_tokens' => 300,
            ]);

        if (! $response->successful()) {
            Log::warning('image search failed', ['status' => $response->status()]);
            return response()->json(['error' => 'Image analysis failed'], 502);
        }

        $text = $response->json('choices.0.message.content', '{}');
        $text = trim(preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $text));

        $result = json_decode($text, true);
        if (! $result || ! isset($result['scene'])) {
            return response()->json(['error' => 'Could not analyze image'], 422);
        }

        return response()->json([
            'scene' => $result['scene'],
            'counties' => $result['counties'] ?? [],
            'attractions' => $result['attractions'] ?? [],
        ]);
    }
}