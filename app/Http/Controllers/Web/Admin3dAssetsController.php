<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CountyInstitution;
use App\Models\Marketplace\Product;
use App\Models\MediaAsset;
use App\Models\MediaDerivative;
use App\Models\Room3d;
use App\Services\MediaLibraryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * Admin3dAssetsController — upload, list, attach, and delete 3D assets
 * (splat files, GLB models, Room3D) for institutions and products.
 *
 * Accessible from:
 *   /kicc-admin/3d-assets/...   — KICC superadmin (all entities)
 *   /institution-admin/{inst}/3d  — per-institution admin
 */
class Admin3dAssetsController extends Controller
{
    protected function authorize(): void
    {
        $user = Auth::user();
        abort_unless($user, 401);
    }

    protected function authorizeKicc(): void
    {
        $user = Auth::user();
        abort_unless($user?->hasRole('kicc_admin'), 403, 'KICC admin access required.');
    }

    /** Resolve institution + verify access; returns null + 403 for non-kicc users. */
    protected function resolveInstitution(?string $slug): ?CountyInstitution
    {
        if (!$slug) return null;
        $inst = CountyInstitution::where('slug', $slug)->firstOrFail();
        $user = Auth::user();
        $allowed = $user->hasRole('kicc_admin')
            || ($user->institution_id === $inst->id)
            || ($user->hasRole('county_admin') && $user->county_id === $inst->county_id);
        abort_unless($allowed, 403, 'Institution access denied.');
        return $inst;
    }

    // ── List 3D assets ──

    public function index(Request $request, ?string $institution = null)
    {
        $this->authorize();
        $inst = $institution ? $this->resolveInstitution($institution) : null;

        $query = MediaAsset::where('kind', 'model')->orWhereIn('id', function ($q) {
            $q->select('media_asset_id')->from('media_derivatives')->whereIn('kind', ['model_glb', 'model_splat']);
        });

        if ($inst) {
            $query = $query->where('owner_type', CountyInstitution::class)->where('owner_id', $inst->id);
        }

        $assets = $query->with('derivatives')->latest()->take(100)->get();

        $institutions = CountyInstitution::orderBy('name')->get(['id', 'name', 'slug']);
        $room3ds = $inst
            ? Room3d::where('entity_type', CountyInstitution::class)->where('entity_id', $inst->id)->latest()->get()
            : Room3d::latest()->take(50)->get();

        return view('admin.3d.index', compact('assets', 'institutions', 'room3ds', 'inst', 'institution'));
    }

    // ── Upload 3D asset form + POST ──

    public function upload(Request $request, ?string $institution = null)
    {
        $this->authorize();
        $inst = $institution ? $this->resolveInstitution($institution) : null;

        if ($request->isMethod('get')) {
            $institutions = CountyInstitution::orderBy('name')->get(['id', 'name', 'slug']);
            $products = $inst ? $this->loadInstitutionProducts($inst) : [];
            return view('admin.3d.upload', compact('institutions', 'products', 'inst'));
        }

        // POST — handle file upload
        $file = $request->file('asset_file');
        abort_unless($file, 422, 'No file provided.');
        abort_unless($file->isValid() && $file->getSize() > 0, 422, 'Invalid or empty file.');

        $mime = $file->getClientOriginalMime() ?? 'application/octet-stream';
        $ext = strtolower(pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));
        $allowedExts = ['splat', 'glb', 'gltf', 'ply'];
        abort_unless(in_array($ext, $allowedExts, true), 422, "Unsupported format: .{$ext}. Allowed: " . implode(', ', $allowedExts));

        // Store to R2 via MediaLibraryService
        $library = app(MediaLibraryService::class);
        $asset = $library->store(
            $file,
            disk: 'r2',
            kind: $ext === 'splat' ? 'model' : 'model',
        );

        // Register derivative
        $derivativeKind = $ext === 'splat' ? 'model_splat' : 'model_glb';
        MediaDerivative::create([
            'media_asset_id' => $asset->id,
            'kind' => $derivativeKind,
            'path' => $asset->url(),
            'mime' => $mime,
            'size_bytes' => $file->getSize(),
            'meta' => ['original_name' => $file->getClientOriginalName()],
        ]);

        // Attach to entity if requested
        $entityType = $request->input('entity_type');
        $entityId = $request->integer('entity_id');
        if ($entityType && $entityId) {
            $asset->attachTo($entityType, $entityId);
        }

        Log::info('3D asset uploaded', ['asset_id' => $asset->id, 'kind' => $derivativeKind, 'file' => $file->getClientOriginalName()]);

        return redirect()->route($institution ? 'admin.3d.institution' : 'admin.3d.assets', $institution ? ['institution' => $institution] : [])
            ->with('success', '3D asset uploaded successfully.');
    }

    // ── Attach existing MediaAsset to entity ──

    public function attach(Request $request, int $assetId)
    {
        $this->authorize();
        $asset = MediaAsset::findOrFail($assetId);

        $entityType = $request->input('entity_type');
        $entityId = $request->integer('entity_id');

        abort_unless($entityType && $entityId, 422, 'entity_type and entity_id required');

        $asset->owner_type = $entityType;
        $asset->owner_id = $entityId;
        $asset->save();

        Log::info('3D asset attached', ['asset_id' => $assetId, 'entity' => "{$entityType}#{$entityId}"]);

        return redirect()->back()->with('success', '3D asset attached.');
    }

    // ── Detach asset from entity ──

    public function detach(int $assetId)
    {
        $this->authorize();
        $asset = MediaAsset::findOrFail($assetId);
        $asset->owner_type = null;
        $asset->owner_id = null;
        $asset->save();

        return redirect()->back()->with('success', '3D asset detached.');
    }

    // ── Delete 3D asset + derivatives + R2 file ──

    public function delete(int $assetId)
    {
        $this->authorizeKicc();

        $asset = MediaAsset::with('derivatives')->findOrFail($assetId);

        foreach ($asset->derivatives as $derivative) {
            try { Storage::disk('r2')->delete($derivative->path); } catch (\Throwable $e) {}
            $derivative->delete();
        }

        try { Storage::disk('r2')->delete($asset->path); } catch (\Throwable $e) {}
        $asset->delete();

        Log::info('3D asset deleted', ['asset_id' => $assetId]);

        return redirect()->back()->with('success', '3D asset deleted.');
    }

    // ── Room3D creation for institutions ──

    public function storeRoom3d(Request $request, string $institution)
    {
        $inst = $this->resolveInstitution($institution);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image_paths' => 'nullable|array',
        ]);

        $room = Room3d::create([
            'user_id' => Auth::id(),
            'title' => $data['title'],
            'slug' => Str::slug($data['title']) . '-' . uniqid(),
            'description' => $data['description'],
            'image_paths' => $data['image_paths'] ?? [],
            'entity_type' => CountyInstitution::class,
            'entity_id' => $inst->id,
            'status' => 'draft',
        ]);

        Log::info('Room3D created for institution', ['room_id' => $room->id, 'institution' => $inst->slug]);

        return redirect()->route('admin.3d.institution', ['institution' => $institution])
            ->with('success', 'Virtual room created for ' . $inst->name);
    }

    // ── Helpers ──

    private function loadInstitutionProducts(CountyInstitution $inst): array
    {
        $productsJson = $inst->products;
        if (!$productsJson || !is_array($productsJson)) return [];
        return array_map($productsJson, fn ($p, $i) => ['index' => $i, 'name' => $p['name'] ?? "Product #{$i}"]);
    }
}