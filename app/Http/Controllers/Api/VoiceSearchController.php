<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Voice search — browser SpeechRecognition (on-device) preferred.
 * This server endpoint is the fallback for browsers that don't support the
 * Web Speech API, or for mobile app integrations. Accepts an audio upload
 * and transcribes it via OpenRouter (Whisper-compatible models).
 */
class VoiceSearchController extends Controller
{
    public function transcribe(Request $request): JsonResponse
    {
        $request->validate([
            'audio' => 'required|file|mimes:wav,mp3,ogg,webm|max:5120',
        ]);

        $file = $request->file('audio');
        $path = $file->getPathname();
        $mime = $file->getMimeType();
        $data = base64_encode(file_get_contents($path));

        $apiKey = config('services.openrouter.key');
        if (! $apiKey) {
            return response()->json(['error' => 'transcription service not configured'], 503);
        }

        // OpenRouter doesn't natively support audio transcription like Whisper API.
        // Use a multimodal model that can process audio data.
        $response = Http::withToken($apiKey)
            ->timeout(60)
            ->post('https://openrouter.ai/api/v1/chat/completions', [
                'model' => 'openai/gpt-4o-audio-preview',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => 'Transcribe the speech in this audio accurately. Return ONLY the transcribed text, nothing else.'],
                            ['type' => 'audio', 'audio' => ['data' => $data, 'format' => pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION)]],
                        ],
                    ],
                ],
                'max_tokens' => 256,
            ]);

        if (! $response->successful()) {
            Log::warning('voice transcription failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return response()->json(['error' => 'transcription failed'], 502);
        }

        $text = trim($response->json('choices.0.message.content', ''));

        if (! $text) {
            return response()->json(['error' => 'no speech detected'], 422);
        }

        return response()->json([
            'text' => $text,
            'query' => $text,
        ]);
    }
}