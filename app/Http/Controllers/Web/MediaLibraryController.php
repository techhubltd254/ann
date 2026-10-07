<?php

namespace App\Http\Controllers\Web;

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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MediaLibraryController extends Controller
{
    protected function authorizeMediaAccess(): void
    {
        if (!auth()->user()?->hasAnyRole(['kicc_admin', 'county_admin', 'national_admin', 'exhibitor', 'provider'])) {
            abort(403, 'Media library requires an admin role.');
        }
    }

    public function index(Request $request): View
    {
        $this->authorizeMediaAccess();

        $kind = $request->get('kind');
        $search = $request->get('q');
        $status = $request->get('status');

        $assets = MediaAsset::query()
            ->with(['derivatives', 'pipelineJobs' => fn ($q) => $q->latest()])
            ->when($kind, fn ($q, $k) => $q->where('kind', $k))
            ->when($status, fn ($q, $s) => $q->where('status', $s))
            ->when($search, fn ($q, $s) => $q->where('original_name', 'like', "%{$s}%"))
            ->latest()
            ->paginate(24)
            ->withQueryString();

        $counts = [
            'all' => MediaAsset::count(),
            'image' => MediaAsset::where('kind', 'image')->count(),
            'video' => MediaAsset::where('kind', 'video')->count(),
            'model' => MediaAsset::where('kind', 'model')->count(),
            'ready' => MediaAsset::where('status', 'ready')->count(),
            'processing' => MediaAsset::where('status', 'processing')->count(),
        ];

        $attachables = [
            'counties' => County::orderBy('name')->pluck('name', 'id'),
            'venues' => Venue::orderBy('name')->pluck('name', 'id'),
            'exhibitions' => Exhibition::orderBy('name')->pluck('name', 'id'),
        ];

        return view('admin.media.index', compact('assets', 'counts', 'attachables', 'kind', 'search', 'status'));
    }

    protected function attachmentOptions(): array
    {
        $registry = config('pipeline.attachments', []);

        $options = [];
        foreach ($registry as $class => $slots) {
            $options[$class] = [
                'class' => $class,
                'label' => class_basename($class) . 's',
                'slots' => $slots,
            ];
        }

        return $options;
    }

    public function upload(): View
    {
        $this->authorizeMediaAccess();

        return view('admin.media.upload');
    }

    public function store(Request $request, MediaLibraryService $library): RedirectResponse
    {
        $this->authorizeMediaAccess();

        $request->validate([
            'files' => ['required', 'array', 'max:10'],
            'files.*' => ['file', 'max:2048000'],
        ]);

        foreach ($request->file('files', []) as $file) {
            $library->store($file, ['alt_text' => $request->get('alt_text')]);
        }

        return redirect()->route('media.library')->with('success', count($request->file('files')) . ' file(s) uploaded. Pick one to run the pipeline.');
    }

    public function show(MediaAsset $asset, PipelineService $pipeline): View
    {
        $this->authorizeMediaAccess();

        $asset->load(['derivatives', 'pipelineJobs' => fn ($q) => $q->latest()]);

        $engines = collect($pipeline->allEngineDefinitions())
            ->map(fn ($e, $key) => [
                'key' => $key,
                ...$e,
                'available' => app($e['class'])->available(),
                'note' => app($e['class'])->availabilityNote(),
            ])
            ->filter(fn ($e) => $e['enabled'] || $e['available'])
            ->groupBy(fn ($e) => $e['type'] === 'api' ? 'API engines' : 'Local engines')
            ->all();

        $attachmentOptions = $this->attachmentOptions();
        $slots = [];
        $entities = [];
        foreach ($attachmentOptions as $class => $def) {
            $slots[$class] = $def['slots'];
            $entities[$class] = $class::orderBy('name')->pluck('name', 'id');
        }

        return view('admin.media.show', compact('asset', 'engines', 'attachmentOptions', 'slots', 'entities'));
    }

    public function attach(Request $request, MediaAsset $asset): RedirectResponse
    {
        $this->authorizeMediaAccess();

        $request->validate([
            'entity_type' => ['required', 'string'],
            'entity_id' => ['required', 'integer'],
            'slot' => ['required', 'string'],
        ]);

        $class = $request->input('entity_type');
        $registry = config('pipeline.attachments', []);

        if (!isset($registry[$class])) {
            abort(422, 'Unsupported entity type for attachments.');
        }

        if (!array_key_exists($request->input('slot'), $registry[$class])) {
            abort(422, 'Slot is not allowed for this entity type.');
        }

        $entity = $class::find($request->input('entity_id'));
        if (!$entity) {
            abort(422, 'Entity not found.');
        }

        if ($asset->status !== 'ready') {
            return back()->with('error', 'Asset must be ready before attaching (finish the pipeline first).');
        }

        $asset->forceFill([
            'owner_id' => $entity->id,
            'owner_type' => $class,
            'slot' => $request->input('slot'),
        ])->save();

        return redirect()->route('media.show', $asset)->with(
            'success',
            "Attached to {$entity->name} as " . $registry[$class][$request->input('slot')] . '.'
        );
    }

    public function detach(MediaAsset $asset): RedirectResponse
    {
        $this->authorizeMediaAccess();

        $asset->forceFill(['owner_id' => null, 'owner_type' => null, 'slot' => null])->save();

        return redirect()->route('media.show', $asset)->with('success', 'Asset detached. It stays in the library.');
    }

    public function dispatchPipeline(Request $request, MediaAsset $asset, PipelineService $pipeline): RedirectResponse
    {
        $this->authorizeMediaAccess();

        $request->validate([
            'engine' => ['required', 'string'],
            'pipeline' => ['required', 'in:cinematic_video,image_to_3d'],
        ]);

        $job = $pipeline->dispatch(
            asset: $asset,
            pipeline: $request->input('pipeline'),
            engine: $request->input('engine'),
            options: $request->only(['prompt', 'duration', 'camera_path', 'model', 'aspect_ratio', 'seed']),
        );

        $asset->forceFill(['status' => 'processing'])->save();

        return redirect()->route('media.show', $asset)->with('job_started', $job->id);
    }

    public function jobStatus(PipelineJob $job, PipelineService $pipeline): JsonResponse
    {
        return response()->json($pipeline->status($job));
    }

    public function cancelJob(PipelineJob $job, PipelineService $pipeline): RedirectResponse
    {
        $this->authorizeMediaAccess();

        $pipeline->cancel($job);

        return redirect()->route('media.show', $job->media_asset_id)->with('success', 'Pipeline job cancelled.');
    }

    public function destroy(MediaAsset $asset, MediaLibraryService $library): RedirectResponse
    {
        $this->authorizeMediaAccess();

        $ownerType = $asset->owner_type;
        $ownerId = $asset->owner_id;
        $library->delete($asset);

        $sync = app(\App\Services\CacheSyncService::class);
        if (str_contains($ownerType ?? '', 'County')) {
            $sync->county((int) $ownerId);
        } else {
            $sync->kicc();
        }

        return redirect()->route('media.library')->with('success', 'Asset deleted.');
    }

    /** Generate a presigned upload URL for direct R2 upload — bypasses Cloudflare 100MB limit. */
    public function presignedUploadUrl(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorizeMediaAccess();
        $data = $request->validate([
            'path' => 'required|string|max:500',
            'mime' => 'required|string|max:100',
        ]);
        $svc = app(\App\Services\R2PresignedUploadService::class);
        $result = $svc->generateUploadPresignedUrl($data['path'], $data['mime']);
        return response()->json($result);
    }

    /** Confirm a completed R2 direct upload — create the MediaAsset record. */
    public function confirmR2Upload(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorizeMediaAccess();
        $data = $request->validate([
            'path' => 'required|string|max:500',
            'owner_type' => 'required|string|max:200',
            'owner_id' => 'required|integer',
            'slot' => 'required|string|max:100',
            'original_name' => 'required|string|max:255',
            'mime' => 'required|string|max:100',
            'size_bytes' => 'required|integer|min:1',
        ]);

        // Remove old asset in this slot if it exists
        \App\Models\MediaAsset::forSlot($data['owner_type'], $data['owner_id'], $data['slot'])->delete();

        $asset = \App\Models\MediaAsset::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'owner_id' => $data['owner_id'],
            'owner_type' => $data['owner_type'],
            'slot' => $data['slot'],
            'disk' => 'r2',
            'path' => $data['path'],
            'original_name' => $data['original_name'],
            'mime' => $data['mime'],
            'kind' => str_contains($data['mime'], 'video') ? 'video' : 'image',
            'size_bytes' => $data['size_bytes'],
            'status' => 'ready',
            'uploadedByUserId' => auth()->id() ?? 1,
        ]);
        \App\Models\MediaDerivative::create([
            'media_asset_id' => $asset->id,
            'kind' => str_contains($data['mime'], 'video') ? 'video_mp4' : 'original',
            'path' => $data['path'],
            'mime' => $data['mime'],
            'size_bytes' => $data['size_bytes'],
            'variant' => '1080p',
        ]);

        return response()->json(['asset_id' => $asset->id, 'path' => $data['path']]);
    }
}
