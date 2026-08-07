<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\Exhibition;
use App\Models\MediaAsset;
use App\Models\PipelineJob;
use App\Models\Product;
use App\Models\Venue;
use App\Services\MediaLibraryService;
use App\Services\Pipeline\PipelineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * JSON API for the kicc-web Media Library SPA.
 * Mirrors the Blade MediaLibraryController operations so the admin SPA
 * (kicc-web.pages.dev) operates on the same real MediaAsset rows.
 */
class MediaApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $kind = $request->string('kind')->toString();
        $search = $request->string('q')->toString();
        $status = $request->string('status')->toString();

        $assets = MediaAsset::query()
            ->with(['derivatives', 'pipelineJobs' => fn ($q) => $q->latest()])
            ->when($kind, fn ($q, $k) => $q->where('kind', $k))
            ->when($status, fn ($q, $s) => $q->where('status', $s))
            ->when($search, fn ($q, $s) => $q->where('original_name', 'like', "%{$s}%"))
            ->latest()
            ->limit(100)
            ->get();

        $counts = [
            'All' => MediaAsset::count(),
            'Images' => MediaAsset::where('kind', 'image')->count(),
            'Videos' => MediaAsset::where('kind', 'video')->count(),
            'Models' => MediaAsset::where('kind', 'model')->count(),
            'Ready' => MediaAsset::where('status', 'ready')->count(),
            'Processing' => MediaAsset::where('status', 'processing')->count() + MediaAsset::where('status', 'uploaded')->count(),
        ];

        return response()->json([
            'assets' => $assets->map(fn (MediaAsset $a) => $this->shape($a)),
            'counts' => $counts,
        ]);
    }

    public function store(Request $request, MediaLibraryService $library): JsonResponse
    {
        $request->validate([
            'files' => ['required', 'array', 'max:10'],
            'files.*' => ['file', 'max:51200'],
        ]);

        $stored = [];
        foreach ($request->file('files', []) as $file) {
            $asset = $library->store($file, ['alt_text' => $request->get('alt_text')]);
            $stored[] = $this->shape($asset);
        }

        return response()->json(['assets' => $stored, 'count' => count($stored)], 201);
    }

    public function show(MediaAsset $asset, PipelineService $pipeline): JsonResponse
    {
        $asset->load(['derivatives', 'pipelineJobs' => fn ($q) => $q->latest()]);

        $engines = collect($pipeline->allEngineDefinitions())
            ->filter(fn ($e) => $e['enabled'])
            ->map(fn ($e, $key) => [
                'key' => $key,
                'label' => $e['label'],
                'description' => $e['description'],
                'cost' => $e['cost'],
                'available' => app($e['class'])->available(),
                'note' => app($e['class'])->availabilityNote(),
                'pipeline' => $e['pipeline'] ?? [],
            ])
            ->values()
            ->all();

        return response()->json([
            'asset' => $this->shape($asset),
            'engines' => $engines,
            'attachment' => $this->attachmentOptions(),
        ]);
    }

    public function dispatch(Request $request, MediaAsset $asset, PipelineService $pipeline): JsonResponse
    {
        $request->validate([
            'engine' => ['required', 'string'],
            'pipeline' => ['required', 'in:cinematic_video,image_to_3d'],
        ]);

        $job = $pipeline->dispatch(
            asset: $asset,
            pipeline: $request->input('pipeline'),
            engine: $request->input('engine'),
            options: $request->only(['prompt', 'duration', 'camera', 'aspect', 'seed']),
        );

        $asset->forceFill(['status' => 'processing'])->save();

        return response()->json(['job_id' => $job->id, 'status' => 'queued'], 202);
    }

    public function jobStatus(PipelineJob $job, PipelineService $pipeline): JsonResponse
    {
        return response()->json($pipeline->status($job));
    }

    public function attach(Request $request, MediaAsset $asset): JsonResponse
    {
        $request->validate([
            'entity_type' => ['required', 'string'],
            'entity_id' => ['required', 'integer'],
            'slot' => ['required', 'string'],
        ]);

        $class = $request->input('entity_type');
        $registry = config('pipeline.attachments', []);

        if (!isset($registry[$class])) {
            return response()->json(['error' => 'Unsupported entity type for attachments.'], 422);
        }

        if (!array_key_exists($request->input('slot'), $registry[$class])) {
            return response()->json(['error' => 'Slot is not allowed for this entity type.'], 422);
        }

        $entity = $class::find($request->input('entity_id'));
        if (!$entity) {
            return response()->json(['error' => 'Entity not found.'], 422);
        }

        if ($asset->status !== 'ready') {
            return response()->json(['error' => 'Asset must be ready before attaching (finish the pipeline first).'], 422);
        }

        $asset->forceFill([
            'owner_id' => $entity->id,
            'owner_type' => $class,
            'slot' => $request->input('slot'),
        ])->save();

        return response()->json(['asset' => $this->shape($asset)]);
    }

    public function detach(MediaAsset $asset): JsonResponse
    {
        $asset->forceFill(['owner_id' => null, 'owner_type' => null, 'slot' => null])->save();

        return response()->json(['asset' => $this->shape($asset)]);
    }

    public function destroy(MediaAsset $asset, MediaLibraryService $library): JsonResponse
    {
        $library->delete($asset);

        return response()->json(['ok' => true]);
    }

    private function attachmentOptions(): array
    {
        $registry = config('pipeline.attachments', []);

        $options = [];
        foreach ($registry as $class => $slots) {
            $label = match ($class) {
                County::class => 'Counties',
                Product::class => 'Products',
                Venue::class => 'Venues',
                Exhibition::class => 'Exhibitions',
                default => class_basename($class) . 's',
            };
            $entities = $class::orderBy('name')->pluck('name', 'id')->map(fn ($n, $id) => (string) $id)->all();

            $options[] = [
                'entityType' => $class,
                'label' => $label,
                'slots' => $slots,
                'entities' => $entities,
            ];
        }

        return $options;
    }

    private function shape(MediaAsset $asset): array
    {
        $owner = $asset->owner;
        $pipelineJobs = $asset->relationLoaded('pipelineJobs') ? $asset->pipelineJobs : collect();

        return [
            'id' => $asset->id,
            'uuid' => $asset->uuid,
            'originalName' => $asset->original_name,
            'kind' => $asset->kind,
            'status' => $asset->status,
            'poster' => $asset->posterUrl() ?? $asset->url(),
            'webm' => $asset->webmUrl(),
            'mp4' => $asset->mp4Url(),
            'glb' => $asset->glbUrl(),
            'width' => $asset->width,
            'height' => $asset->height,
            'sizeBytes' => (int) $asset->size_bytes,
            'derivatives' => $asset->derivatives->map(fn ($d) => [
                'kind' => $d->kind,
                'sizeBytes' => (int) $d->size_bytes,
                'variant' => $d->variant,
                'path' => $d->path,
            ])->values()->all(),
            'ownerName' => $owner?->name,
            'slot' => $asset->slot,
            'failureReason' => $pipelineJobs->first()?->error ?? null,
            'progress' => $pipelineJobs->first()?->progress,
            'stage' => $pipelineJobs->first()?->status ?? $asset->status,
        ];
    }
}
