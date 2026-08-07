<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AI-assisted UX research & content service.
 * Generates persona insights, UX copy suggestions and WCAG-aware palette pairings
 * for a given viewport context, using Gemini (GOOGLE_AI_API_KEY) with an OpenRouter
 * fallback (OPENROUTER_API_KEY). Degrades to curated local suggestions when no key is set.
 */
class AiUxService
{
    private const FALLBACKS = [
        [
            'persona' => 'County media officer (tourism + diaspora remittance focus)',
            'painPoint' => 'Shows a static hero; needs motion to prove the destination is alive on slow 3G connections.',
            'copy' => 'Show your county in motion — cinematic hero video, compressed for low-bandwidth phones.',
            'palette' => ['#3b82f6', '#2f915c', '#f59e0b'],
        ],
        [
            'persona' => 'Exhibitor preparing booth assets for MSME Expo',
            'painPoint' => 'Uploads a JPEG and gets nothing back; no idea whether the render finishes before the expo.',
            'copy' => 'Upload once, run a cinematic pipeline, attach anywhere — nothing hardcoded.',
            'palette' => ['#8b5cf6', '#06b6d4', '#f97316'],
        ],
        [
            'persona' => 'KICC national content administrator',
            'painPoint' => 'Needs brand consistency across 47 county pages without waiting on designers.',
            'copy' => 'One asset, many surfaces — webm, mp4 and poster generated and attached automatically.',
            'palette' => ['#0ea5e9', '#10b981', '#ef4444'],
        ],
    ];

    public function suggestions(string $context = 'media_library'): array
    {
        $context = preg_replace('/[^a-z0-9_ -]/i', '', $context) ?: 'media_library';
        $suggestions = $this->generateWithAi($context);

        return [
            'context' => $context,
            'suggestions' => $suggestions,
            'source' => count($suggestions) === 3 && $suggestions[0] === self::FALLBACKS[0] ? 'local' : 'ai',
        ];
    }

    private function generateWithAi(string $context): array
    {
        $prompt = <<<PROMPT
You are a senior UX researcher for the KICC Digital Economy Platform, an admin "Media Library" viewport ($context).
Return STRICT JSON (no markdown) — an array of exactly 3 objects, each with keys:
persona (string, one sentence), painPoint (string), copy (string, short CTA/headline), palette (array of exactly 3 hex colors, WCAG AA-paired for dark UI).
Make each entry concrete and specific to Kenyan county media officers, exhibitors, or KICC admins.
PROMPT;

        if ($key = config('services.openrouter.key')) {
            $res = Http::withToken($key)->timeout(30)->post('https://openrouter.ai/api/v1/chat/completions', [
                'model' => 'openrouter/auto',
                'messages' => [
                    ['role' => 'system', 'content' => 'You return STRICT JSON arrays only.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.9,
                'max_tokens' => 1000,
            ]);
            if ($res->successful()) {
                $text = data_get($res->json(), 'choices.0.message.content', '');
                $parsed = json_decode($text, true);
                if (is_array($parsed) && count($parsed) === 3) {
                    return $this->sanitize($parsed);
                }
                Log::warning('AiUxService: malformed OpenRouter response', ['raw' => $text]);
            }
        }

        if ($key = config('services.google_ai.key')) {
            $res = Http::timeout(30)->post(
                'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent',
                [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => ['temperature' => 0.9, 'responseMimeType' => 'application/json'],
                ],
                ['key' => $key]
            );
            if ($res->successful()) {
                $text = data_get($res->json(), 'candidates.0.content.parts.0.text', '');
                $parsed = json_decode($text, true);
                if (is_array($parsed) && count($parsed) === 3) {
                    return $this->sanitize($parsed);
                }
                Log::warning('AiUxService: malformed Gemini response', ['raw' => $text]);
            }
        }

        return self::FALLBACKS;
    }

    private function sanitize(array $list): array
    {
        return collect($list)->map(function ($s) {
            $palette = collect($s['palette'] ?? [])
                ->filter(fn ($c) => is_string($c) && preg_match('/^#[0-9a-fA-F]{6}$/', $c))
                ->take(3)
                ->values()
                ->all();
            return [
                'persona' => substr((string) ($s['persona'] ?? ''), 0, 160),
                'painPoint' => substr((string) ($s['painPoint'] ?? ''), 0, 240),
                'copy' => substr((string) ($s['copy'] ?? ''), 0, 240),
                'palette' => count($palette) === 3 ? $palette : ['#3b82f6', '#2f915c', '#f59e0b'],
            ];
        })->values()->all();
    }
}
