@extends('layouts.app')
@php $isInst = $inst !== null; @endphp

@section('title', 'Upload 3D Asset — KICC Admin')
@section('content')
<div class="max-w-4xl mx-auto px-6 py-8">

    <div class="mb-8">
        <a href="{{ $isInst ? route('admin.3d.institution', ['institution' => $institution]) : route('admin.3d.assets') }}" class="text-sm text-kicc-red hover:underline">&larr; Back to 3D Assets</a>
        <h1 class="text-3xl font-black text-gray-900 mt-2">Upload 3D Asset</h1>
        <p class="text-gray-500 mt-1">Upload splat files (.splat), GLB models (.glb), or PLY point clouds (.ply) to the Cloudflare R2 CDN.</p>
    </div>

    <form method="POST" action="{{ $isInst ? route('admin.3d.upload.institution', ['institution' => $institution]) : route('admin.3d.upload') }}" enctype="multipart/form-data" class="card-kicc p-6 space-y-5">
        @csrf

        {{-- File upload --}}
        <div>
            <label class="label-kicc">3D File</label>
            <div class="mt-1">
                <input type="file" name="asset_file" accept=".splat,.glb,.gltf,.ply"
                       class="input-kicc" required
                       onchange="document.getElementById('file-preview').textContent = this.files[0]?.name || ''">
                <p class="text-xs text-gray-400 mt-1">Accepted: .splat (Gaussian splat), .glb (Draco GLTF), .gltf, .ply (Point cloud)</p>
                <p class="text-xs text-gray-400" id="file-preview"></p>
            </div>
        </div>

        {{-- Entity type --}}
        <div>
            <label class="label-kicc">Attach to Entity</label>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <select name="entity_type" class="input-kicc" onchange="toggleEntitySelect(this.value)">
                        <option value="">— None (unattached) —</option>
                        <option value="App\Models\CountyInstitution">Institution</option>
                        <option value="App\Models\CountyProduct">County Product</option>
                    </select>
                </div>
                <div>
                    <select name="entity_id" class="input-kicc" id="entity-select">
                        <option value="">— Select entity —</option>
                        @if($isInst)
                        <option value="{{ $inst->id }}" selected>{{ $inst->name }}</option>
                        @else
                        @foreach($institutions as $instOpt)
                        <option value="{{ $instOpt->id }}" data-type="App\Models\CountyInstitution">
                            {{ $instOpt->name }}
                        </option>
                        @endforeach
                        @endif
                    </select>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-1">Choose an Institution or Product to attach this 3D model to. It will appear on their public profile page.</p>
        </div>

        <div class="border-t border-gray-200 pt-4">
            <button type="submit" class="btn-kicc btn-kicc-primary w-full justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 15v2-4-2-2-2v-1.5-1.5a1 1 0 11-6 0Z"/></svg>
                Upload & Attach
            </button>
        </div>
    </form>

    <div class="mt-8 card-kicc p-6 text-sm text-gray-600">
        <p class="font-semibold text-gray-800 mb-2">About 3D Asset Types</p>
        <ul class="list-disc space-y-1 pl-5">
            <li><strong>.splat</strong> — Gaussian splat point cloud. Rendered by the WebGL2 splat viewer. Best for capturing real-world objects as photo-realistic 3D.</li>
            <li><strong>.glb</strong> — Draco-compressed GLTF binary. Draco is a Google compression algorithm for 3D meshes — <code>gltf-pipeline -i model.gltf -o model.glb -d</code> to compress.</li>
            <li><strong>.ply</strong> — Polygon point cloud format. Used by the Pipe Dream photogrammetry pipeline.</li>
        </ul>
        <p class="mt-3">All files are stored on the KICC Cloudflare R2 media CDN at <code>media.kicctest.org/storage/</code>.</p>
    </div>
</div>

@push('scripts')
<script>
function toggleEntitySelect(val) {
    var sel = document.getElementById('entity-select');
    if (!val) { sel.value = ''; sel.disabled = true; return; }
    sel.disabled = false;
    // Filter options by data-type if needed
}
document.addEventListener('DOMContentLoaded', function() {
    toggleEntitySelect(document.querySelector('[name="entity_type"]')?.value || '');
});
</script>
@endpush
@endsection