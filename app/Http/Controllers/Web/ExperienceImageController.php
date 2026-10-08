<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\{MediaAsset, MediaDerivative, County, CountyInstitution, Venue, Exhibition, Ministry, Agency};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Storage, Cache, Log};
use Illuminate\Support\Str;

/** Manage canonical R2 image rows; never modify an entity by list position. */
class ExperienceImageController extends Controller
{
    private function authorize(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403, 'KICC administrator permission required.');
    }

    private function owners(): array
    {
        return ['county' => County::class, 'institution' => CountyInstitution::class,
            'venue' => Venue::class, 'exhibition' => Exhibition::class,
            'ministry' => Ministry::class, 'agency' => Agency::class,
            'product' => \App\Models\Marketplace\Product::class];
    }

    public function index(Request $request)
    {
        $this->authorize();
        $assets = MediaAsset::query()->with('derivatives')->where('kind', 'image')
            ->when($request->filled('owner_type') && isset($this->owners()[$request->input('owner_type')]), fn($q) => $q->where('owner_type', $this->owners()[$request->input('owner_type')]))
            ->when($request->filled('owner_id'), fn($q) => $q->where('owner_id', $request->integer('owner_id')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $search = '%' . $request->string('q')->toString() . '%';
                $q->where(fn($q) => $q->where('path', 'like', $search)->orWhere('original_name', 'like', $search)->orWhere('slot', 'like', $search));
            })->orderBy('owner_type')->orderBy('owner_id')->paginate(24)->withQueryString();
        $choices = [];
        foreach ($this->owners() as $type => $class) {
            $choices[$type] = $class::orderBy('name')->get(['id', 'name']);
        }
        return view('experience.admin.images', compact('assets', 'choices'));
    }

    private function validated(Request $request): array
    {
        return $request->validate(['image' => 'required|image|mimes:jpg,jpeg,png,webp|max:10240',
            'alt_text' => 'nullable|string|max:255', 'illustrative' => 'nullable|boolean']);
    }

    private function upload(Request $request, string $prefix): array
    {
        $file = $request->file('image');
        $path = trim($prefix, '/') . '/image/' . Str::uuid() . '.' . $file->extension();
        $disk = Storage::disk('r2');
        $stream = fopen($file->getRealPath(), 'rb');
        try {
            abort_unless($disk->put($path, $stream, ['ContentType' => $file->getMimeType()]) && $disk->exists($path), 503, 'R2 upload was not confirmed.');
        } finally {
            if (is_resource($stream)) fclose($stream);
        }
        $dim = getimagesize($file->getRealPath());
        return ['path' => $path, 'disk' => 'r2', 'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(), 'kind' => 'image', 'size_bytes' => $file->getSize(),
            'width' => $dim[0], 'height' => $dim[1], 'status' => 'ready',
            'alt_text' => $request->input('alt_text', ''),
            'metadata' => ['illustrative' => $request->boolean('illustrative')]];
    }

    private function synchronizeReferences(MediaAsset $asset, array $oldPaths, ?string $newPath): void
    {
        $class = $asset->owner_type;
        if (!in_array($class, $this->owners(), true)) return;
        $owner = $class::find($asset->owner_id);
        if (!$owner) return;
        $fields = match ($class) {
            Venue::class, Exhibition::class => ['cover_image'],
            CountyInstitution::class => ['cover_image_url', 'logo_url'],
            Ministry::class, Agency::class => ['logo'],
            default => [],
        };
        foreach ($fields as $field) {
            $value = (string) ($owner->getAttribute($field) ?? '');
            foreach ($oldPaths as $path) {
                if ($value === $path || str_ends_with($value, '/' . ltrim($path, '/'))) {
                    $owner->setAttribute($field, $newPath ? url('/media/video/' . $newPath) : null);
                    break;
                }
            }
        }
        if ($owner->isDirty()) $owner->save();
        if ($class === \App\Models\Marketplace\Product::class) {
            foreach ($owner->images as $image) {
                foreach ($oldPaths as $path) {
                    if ($image->url === $path || str_ends_with((string) $image->url, '/' . ltrim($path, '/'))) {
                        if ($newPath) $image->update(['url' => url('/media/video/' . $newPath)]);
                        else $image->delete();
                        break;
                    }
                }
            }
        }
    }

    private function invalidate(MediaAsset $asset): void
    {
        // Owner/slot resolution caches must not outlive a replace or delete.
        Cache::forget("resolve:{$asset->owner_type}_{$asset->owner_id}_slot_{$asset->slot}");
        Cache::forget("resolve:county_hero_id_{$asset->owner_id}");
        Cache::forget('kicc_counties_index');
        Cache::forget('thumb_' . $asset->owner_type . '_' . $asset->owner_id);
        foreach (['card','hero'] as $size) Cache::forget('mf:' . str_replace('\\', '_', $asset->owner_type) . ':' . $asset->owner_id . ':' . $size);
    }

    private function retireUnshared(array $paths): void
    {
        foreach (array_unique($paths) as $path) {
            if (!$path) continue;
            if (MediaAsset::where('disk', 'r2')->where('path', $path)->exists() || MediaDerivative::where('path', $path)->exists()) continue;
            try {
                $disk = Storage::disk('r2');
                if (!$disk->delete($path) || $disk->exists($path)) Log::warning('Unreferenced image needs R2 cleanup', ['path' => $path]);
            } catch (\Throwable $e) {
                Log::warning('Unreferenced image cleanup failed', ['path' => $path, 'error' => $e->getMessage()]);
            }
        }
    }

    public function store(Request $request)
    {
        $this->authorize();
        $this->validated($request);
        $data = $request->validate(['owner_type' => 'required|in:county,institution,venue,exhibition,ministry,agency,product',
            'owner_id' => 'required|integer|min:1', 'slot' => 'required|in:fallback_image,hero_image']);
        $class = $this->owners()[$data['owner_type']];
        $owner = $class::findOrFail($data['owner_id']);
        $prefix = $data['owner_type'] === 'county' ? 'counties/' . $owner->slug
            : ($data['owner_type'] === 'institution' ? 'institutions/' . $owner->slug : 'entities/' . $data['owner_type'] . '/' . $owner->id);
        $fields = $this->upload($request, $prefix);
        try {
            $asset = DB::transaction(function () use ($fields, $data, $class) {
                $asset = MediaAsset::create($fields + ['uuid' => (string) Str::uuid(),
                    'owner_type' => $class, 'owner_id' => $data['owner_id'], 'slot' => $data['slot']]);
                $asset->derivatives()->create(['kind' => 'thumb', 'variant' => 'source',
                    'path' => $asset->path, 'mime' => $asset->mime, 'size_bytes' => $asset->size_bytes]);
                return $asset;
            });
        } catch (\Throwable $e) {
            Storage::disk('r2')->delete($fields['path']);
            throw $e;
        }
        $this->invalidate($asset);
        return redirect()->route('experience.images.index')->with('success', 'Image uploaded to R2 and assigned to entity #' . $asset->owner_id . '.');
    }

    public function replace(Request $request, MediaAsset $asset)
    {
        $this->authorize();
        abort_unless($asset->kind === 'image' && $asset->disk === 'r2', 422, 'Only R2 image assets can be replaced here.');
        $this->validated($request);
        // Keep its identity, owner and slot, so every actual reference stays mapped.
        $prefix = str_contains($asset->path, '/image/') ? explode('/image/', $asset->path)[0] : 'entities/' . Str::slug(class_basename($asset->owner_type)) . '/' . $asset->owner_id;
        $fields = $this->upload($request, $prefix);
        $old = array_merge([$asset->path], $asset->derivatives->pluck('path')->all());
        try {
            DB::transaction(function () use ($asset, $fields, $old) {
                $locked = MediaAsset::lockForUpdate()->findOrFail($asset->id);
                $locked->update($fields);
                $this->synchronizeReferences($locked, $old, $fields['path']);
                $locked->derivatives()->delete();
                $locked->derivatives()->create(['kind' => 'thumb', 'variant' => 'source',
                    'path' => $fields['path'], 'mime' => $fields['mime'], 'size_bytes' => $fields['size_bytes']]);
            });
        } catch (\Throwable $e) {
            Storage::disk('r2')->delete($fields['path']);
            throw $e;
        }
        $this->invalidate($asset);
        $this->retireUnshared($old);
        return back()->with('success', 'Image replaced. Entity ID and slot preserved; public pages now read the new R2 object.');
    }

    public function destroy(MediaAsset $asset)
    {
        $this->authorize();
        abort_unless($asset->kind === 'image' && $asset->disk === 'r2', 422, 'Only R2 image assets can be deleted here.');
        $old = array_merge([$asset->path], $asset->derivatives->pluck('path')->all());
        DB::transaction(function () use ($asset, $old) {
            $locked = MediaAsset::lockForUpdate()->findOrFail($asset->id);
            $this->synchronizeReferences($locked, $old, null);
            $locked->derivatives()->delete();
            $locked->delete();
        });
        $this->invalidate($asset);
        $this->retireUnshared($old);
        return back()->with('success', 'Image removed from this entity. Unshared R2 objects cleaned up; shared files retained.');
    }
}
