<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Builds vector embeddings for searchable content (counties, sector entities,
 * products, attractions) into the `embeddings` table for semantic search.
 *
 * Uses OpenRouter (openai/text-embedding-3-small, 1536 dims, multilingual —
 * covers English + Swahili at launch quality). Idempotent via content-hash skip.
 */
class BuildEmbeddings extends Command
{
    protected $signature = 'embeddings:build {--model=openai/text-embedding-3-small}';
    protected $description = 'Generate semantic-search embeddings for all searchable content';

    public function handle(): int
    {
        $model = $this->option('model');
        $items = collect();

        foreach (DB::table('counties')->where('is_active', 1)->get(['id', 'name', 'tagline', 'description']) as $c) {
            $items->push(['type' => 'county', 'id' => $c->id, 'text' => trim("{$c->name} County. {$c->tagline} {$c->description}")]);
        }
        foreach (DB::table('sector_entities')->where('is_published', 1)->get(['id', 'name', 'description', 'sector_type']) as $e) {
            $items->push(['type' => 'sector_entity', 'id' => $e->id, 'text' => trim("{$e->name} ({$e->sector_type}). {$e->description}")]);
        }
        foreach (DB::table('products')->get(['id', 'name', 'description']) as $p) {
            $items->push(['type' => 'product', 'id' => $p->id, 'text' => trim("{$p->name}. {$p->description}")]);
        }
        foreach (DB::table('county_tourism_attractions')->where('is_published', 1)->get(['id', 'name', 'description', 'category']) as $a) {
            $items->push(['type' => 'attraction', 'id' => $a->id, 'text' => trim("{$a->name} ({$a->category}). {$a->description}")]);
        }

        $modelId = DB::table('ml_models')->where('name', $model)->value('id')
            ?? DB::table('ml_models')->insertGetId([
                'name' => $model,
                'slug' => str_replace('/', '-', $model),
                'model_type' => 'embedding',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        $built = 0; $skipped = 0;
        foreach ($items as $item) {
            $hash = md5($item['text']);
            $existing = DB::table('embeddings')
                ->where('embeddable_type', $item['type'])
                ->where('embeddable_id', $item['id'])
                ->where('model_id', $modelId)
                ->first();
            if ($existing && ($existing->metadata ? json_decode($existing->metadata, true)['hash'] ?? null : null) === $hash) {
                $skipped++;
                continue;
            }

            $vector = $this->embed($item['text'], $model);
            if (! $vector) {
                $this->warn("embed failed for {$item['type']}#{$item['id']}");
                continue;
            }

            DB::table('embeddings')->updateOrInsert(
                ['embeddable_type' => $item['type'], 'embeddable_id' => $item['id'], 'model_id' => $modelId],
                [
                    'vector' => json_encode($vector),
                    'dimension' => count($vector),
                    'metadata' => json_encode(['hash' => $hash, 'preview' => mb_substr($item['text'], 0, 120)]),
                    'created_at' => now(),
                ]
            );
            $built++;
        }

        $this->info("embeddings: {$built} built, {$skipped} skipped (unchanged), " . $items->count() . ' total');
        return self::SUCCESS;
    }

    private function embed(string $text, string $model): ?array
    {
        $resp = Http::withToken(config('services.openrouter.key'))
            ->timeout(30)
            ->post('https://openrouter.ai/api/v1/embeddings', [
                'model' => $model,
                'input' => mb_substr($text, 0, 2000),
            ]);
        if (! $resp->successful()) {
            return null;
        }
        return $resp->json('data.0.embedding');
    }
}
