<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Semantic (vector) search across counties, sector entities, products, attractions.
 * Query is embedded via OpenRouter; cosine similarity ranks stored embeddings.
 */
class SemanticSearchController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['error' => 'query too short'], 422);
        }
        $limit = min(20, (int) $request->query('limit', 10));

        $queryVector = $this->embed($q);
        if (! $queryVector) {
            return response()->json(['error' => 'embedding service unavailable'], 503);
        }

        // Stream rows and keep only a fixed-size top-N heap — constant memory at any scale.
        $top = []; // sorted desc by score
        DB::table('embeddings')->select(['embeddable_type', 'embeddable_id', 'vector', 'metadata'])
            ->orderBy('id')
            ->chunk(500, function ($rows) use (&$top, $queryVector, $limit) {
                foreach ($rows as $row) {
                    $vector = json_decode($row->vector, true);
                    if (! is_array($vector)) continue;
                    $score = $this->cosine($queryVector, $vector);
                    $top[] = [
                        'type' => $row->embeddable_type,
                        'id' => $row->embeddable_id,
                        'score' => $score,
                        'preview' => json_decode($row->metadata ?? '{}', true)['preview'] ?? null,
                    ];
                }
                usort($top, fn ($a, $b) => $b['score'] <=> $a['score']);
                $top = array_slice($top, 0, $limit);
            });

        $hydrated = $this->hydrate($top);

        return response()->json([
            'query' => $q,
            'count' => count($hydrated),
            'results' => $hydrated,
        ]);
    }

    private function hydrate(array $top): array
    {
        $byType = collect($top)->groupBy('type');
        $out = [];
        foreach ($byType as $type => $items) {
            $ids = collect($items)->pluck('id');
            $table = match ($type) {
                'county' => 'counties',
                'product' => 'products',
                'attraction' => 'county_tourism_attractions',
                default => 'sector_entities',
            };
            $rows = DB::table($table)->whereIn('id', $ids)->get(['id', 'name'])->keyBy('id');
            foreach ($items as $item) {
                $row = $rows[$item['id']] ?? null;
                if (! $row) continue;
                $out[] = $item + [
                    'name' => $row->name,
                    'url' => $this->urlFor($type, $row),
                ];
            }
        }
        usort($out, fn ($a, $b) => $b['score'] <=> $a['score']);
        return $out;
    }

    private function urlFor(string $type, object $row): ?string
    {
        return match ($type) {
            'county' => '/counties/' . ($row->slug ?? $row->id),
            'product' => '/marketplace',
            'attraction' => '/attractions/' . $row->id,
            default => null,
        };
    }

    private function embed(string $text): ?array
    {
        $resp = Http::withToken(config('services.openrouter.key'))
            ->timeout(30)
            ->post('https://openrouter.ai/api/v1/embeddings', [
                'model' => 'openai/text-embedding-3-small',
                'input' => mb_substr($text, 0, 2000),
            ]);
        return $resp->successful() ? $resp->json('data.0.embedding') : null;
    }

    private function cosine(array $a, array $b): float
    {
        $dot = 0.0; $na = 0.0; $nb = 0.0;
        $n = min(count($a), count($b));
        for ($i = 0; $i < $n; $i++) {
            $dot += $a[$i] * $b[$i];
            $na += $a[$i] * $a[$i];
            $nb += $b[$i] * $b[$i];
        }
        return ($na > 0 && $nb > 0) ? $dot / (sqrt($na) * sqrt($nb)) : 0.0;
    }
}
