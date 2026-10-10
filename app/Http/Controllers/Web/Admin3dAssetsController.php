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
use Illuminate\Support\Str;

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
        abort_unless(app(\App\Services\AdminHierarchyScope::class)->canInstitution($user,$inst),403,'Institution access denied.');
        return $inst;
    }

    // ── List 3D assets ──

    public function index(Request $request, ?string $institution = null)
    {
        $this->authorize();
        $inst = $institution ? $this->resolveInstitution($institution) : null;

        $query = MediaAsset::where(function($q){$q->where('kind','model')->orWhereIn('id',function($q){
            $q->select('media_asset_id')->from('media_derivatives')->whereIn('kind', ['model_glb', 'model_splat']);
        });});

        if ($inst) {
             $productIds=Product::where('institution_id',$inst->id)->pluck('id');$query=$query->where(fn($q)=>$q->where(fn($q)=>$q->where('owner_type',CountyInstitution::class)->where('owner_id',$inst->id))->orWhere(fn($q)=>$q->where('owner_type',Product::class)->whereIn('owner_id',$productIds)));
        }

        $assets = $query->with('derivatives')->latest()->take(100)->get();

        $institutions = app(\App\Services\AdminHierarchyScope::class)->institutions(Auth::user())->orderBy('name')->get(['id','name','slug']);
        $room3ds = $inst
            ? Room3d::where('entity_type', CountyInstitution::class)->where('entity_id', $inst->id)->latest()->get()
            : Room3d::latest()->take(50)->get();

        return view('experience.pages.admin.3d.index', compact('assets', 'institutions', 'room3ds', 'inst', 'institution'));
    }

    // ── Upload 3D asset form + POST ──

    public function upload(Request $request, ?string $institution = null)
    {
        $this->authorize();
        $inst = $institution ? $this->resolveInstitution($institution) : null;

        if ($request->isMethod('get')) {
            $institutions = app(\App\Services\AdminHierarchyScope::class)->institutions(Auth::user())->orderBy('name')->get(['id','name','slug']);
            $products = $inst ? $this->loadInstitutionProducts($inst) : [];
            return view('experience.pages.admin.3d.upload', compact('institutions', 'products', 'inst','institution'));
        }

        // POST — handle file upload
        $file = $request->file('asset_file');
        abort_unless($file, 422, 'No file provided.');
        abort_unless($file->isValid() && $file->getSize() > 0, 422, 'Invalid or empty file.');

        $mime = $file->getClientOriginalMime() ?? 'application/octet-stream';
        $ext = strtolower(pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));
        $allowedExts = ['splat', 'glb', 'gltf', 'ply'];
        abort_unless(in_array($ext, $allowedExts, true), 422, "Unsupported format: .{$ext}. Allowed: " . implode(', ', $allowedExts));

        abort_unless($file->getSize()<=50*1024*1024,422,'3D form limit is 50 MiB. Videos use the separate resumable 2 GiB workflow.');
        if($ext==='glb'){ $head=file_get_contents($file->getRealPath(),false,null,0,12);abort_unless(strlen($head)===12&&substr($head,0,4)==='glTF'&&unpack('V',substr($head,4,4))[1]===2&&unpack('V',substr($head,8,4))[1]===$file->getSize(),422,'Invalid GLB container.'); }
        if($ext==='gltf'){$json=json_decode(file_get_contents($file->getRealPath()),true);abort_unless(($json['asset']['version']??'')==='2.0',422,'Invalid glTF 2.0 file.');foreach(array_merge($json['buffers']??[],$json['images']??[]) as $part)abort_if(isset($part['uri'])&&!str_starts_with($part['uri'],'data:'),422,'Upload a self-contained GLB or embedded glTF; external texture files are not included.');}
        $entityType=$inst?CountyInstitution::class:$request->input('entity_type');$entityId=$inst?$inst->id:$request->integer('entity_id');
        if($inst&&$request->input('entity_type')===Product::class){$entityType=Product::class;$entityId=$request->integer('entity_id');}
        if($entityType===CountyInstitution::class){$owner=CountyInstitution::findOrFail($entityId);abort_unless(app(\App\Services\AdminHierarchyScope::class)->canInstitution(Auth::user(),$owner),403);}
        elseif($entityType===Product::class){$owner=Product::findOrFail($entityId);$i=CountyInstitution::findOrFail($owner->institution_id);abort_unless(app(\App\Services\AdminHierarchyScope::class)->canInstitution(Auth::user(),$i)&&(!$inst||$inst->id===$i->id),403);}
        else abort(422,'Choose the responsible institution or a real marketplace product.');
        $path='models/'.($inst?->slug??'catalogue').'/'.Str::uuid().'.'.$ext;$disk=Storage::disk('r2');$in=fopen($file->getRealPath(),'rb');try{$ok=$disk->put($path,$in,['ContentType'=>$ext==='glb'?'model/gltf-binary':($ext==='gltf'?'model/gltf+json':'application/octet-stream')]);}finally{fclose($in);}abort_unless($ok&&$disk->size($path)===$file->getSize(),503,'Model storage verification failed.');
        $asset=MediaAsset::create(['uuid'=>(string)Str::uuid(),'disk'=>'r2','path'=>$path,'kind'=>'model','mime'=>$ext==='glb'?'model/gltf-binary':'application/octet-stream','size_bytes'=>$file->getSize(),'original_name'=>$file->getClientOriginalName(),'status'=>'ready','owner_type'=>$entityType,'owner_id'=>$entityId,'slot'=>'model_3d','alt_text'=>$file->getClientOriginalName()]);
        $derivativeKind=match($ext){'splat'=>'model_splat','ply'=>'model_ply',default=>'model_glb'};
        $asset->derivatives()->create(['kind'=>$derivativeKind,'variant'=>'source','path'=>$path,'mime'=>$asset->mime,'size_bytes'=>$asset->size_bytes]);\Illuminate\Support\Facades\Cache::increment('kicc_cache_version');
        Log::info('3D asset uploaded', ['asset_id' => $asset->id, 'kind' => $derivativeKind, 'file' => $file->getClientOriginalName()]);

        return redirect()->route($institution ? 'admin.3d.institution' : 'admin.3d.assets', $institution ? ['institution' => $institution] : [])
            ->with('success', '3D asset uploaded successfully.');
    }

    // ── Attach existing MediaAsset to entity ──

    public function attach(Request $request, int $assetId)
    {
        $this->authorizeKicc();
        $asset = MediaAsset::findOrFail($assetId);

        $entityType = $request->input('entity_type');
        $entityId = $request->integer('entity_id');

        abort_unless($entityType && $entityId, 422, 'entity_type and entity_id required');
        abort_unless(in_array($entityType,[CountyInstitution::class,Product::class],true),422,'Only native institutions and marketplace products may own models.');
        $owner=$entityType===CountyInstitution::class?CountyInstitution::findOrFail($entityId):CountyInstitution::findOrFail(Product::findOrFail($entityId)->institution_id);
        abort_unless(app(\App\Services\AdminHierarchyScope::class)->canInstitution(Auth::user(),$owner),403);

        $asset->owner_type = $entityType;
        $asset->owner_id = $entityId;
        $asset->save();

        Log::info('3D asset attached', ['asset_id' => $assetId, 'entity' => "{$entityType}#{$entityId}"]);

        return redirect()->back()->with('success', '3D asset attached.');
    }

    // ── Detach asset from entity ──

    public function detach(int $assetId)
    {
        $this->authorizeKicc();
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
            'description' => $data['description']??'',
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
        return Product::where('institution_id',$inst->id)->get(['id','name'])->map(fn($p)=>['id'=>$p->id,'name'=>$p->name])->all();
    }
}
