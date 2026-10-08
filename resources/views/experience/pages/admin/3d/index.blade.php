@extends('layouts.app')
@php $isInst = $inst !== null; @endphp

@section('title', '3D Assets' . ($inst ? ' — ' . $inst->name : '') . ' - KICC Admin')
@section('content')
<div class="max-w-7xl mx-auto px-6 py-8">

    <div class="flex items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-black text-gray-900">3D Assets</h1>
            @if($inst)
            <p class="text-gray-500 mt-1 font-medium">{{ $inst->name }}</p>
            @endif
            <p class="text-sm text-gray-500 mt-1">
                {{ count($assets) }} splat/GLB models &middot; {{ count($room3ds) }} virtual rooms
            </p>
        </div>
        <div class="flex gap-3">
            @if($isInst)
            <a href="{{ route('admin.3d.upload.institution', ['institution' => $institution]) }}"
               class="btn-kicc btn-kicc-primary">Upload 3D Asset</a>
            @else
            <a href="{{ route('admin.3d.upload') }}" class="btn-kicc btn-kicc-primary">Upload 3D Asset</a>
            <div class="relative">
                <select onchange="if(this.value) window.location=this.value" class="input-kicc w-auto">
                    <option value="">Filter by institution...</option>
                    @foreach($institutions as $instOpt)
                    <option value="{{ route('admin.3d.institution', ['institution' => $instOpt->slug]) }}">{{ $instOpt->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
        </div>
    </div>

    {{-- Splat / GLB Assets Table --}}
    <div class="table-kicc-wrap mb-8">
        <table class="table-kicc">
            <thead>
                <tr>
                    <th>File</th>
                    <th>Type</th>
                    <th>Format</th>
                    <th>Entity</th>
                    <th>Size</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($assets as $asset)
                @php
                    $deriv = $asset->derivatives[0] ?? null;
                @endphp
                <tr>
                    <td class="font-mono text-xs">{{ Str::limit($asset->original_name ?? basename($asset->path ?? ''), 40) }}</td>
                    <td>
                        @if($deriv && $deriv->kind === 'model_splat')
                        <span class="badge-kicc badge-kicc-blue">Splat</span>
                        @elseif($deriv && $deriv->kind === 'model_glb')
                        <span class="badge-kicc badge-kicc-green">GLB</span>
                        @else
                        <span class="badge-kicc badge-kicc">Model</span>
                        @endif
                    </td>
                    <td class="text-xs text-gray-500">{{ pathinfo($asset->path ?? '', PATHINFO_EXTENSION) }}</td>
                    <td>
                        @if($asset->owner_type)
                        <span class="text-xs text-gray-600">{{ Str::afterLast($asset->owner_type, '\\Models\\') }} #{{ $asset->owner_id }}</span>
                        @else
                        <span class="text-gray-400 text-xs">Unattached</span>
                        @endif
                    </td>
                    <td class="text-xs text-gray-500">{{ $deriv ? number_format($deriv->size_bytes) . ' B' : '—' }}</td>
                    <td class="flex gap-2">
                        <a href="{{ $asset->url() }}" target="_blank" class="text-xs text-kicc-red hover:underline">View</a>
                        <form method="POST" action="{{ route('admin.3d.detach', ['assetId' => $asset->id]) }}" class="inline"
                              onsubmit="return confirm('Detach this 3D asset from its entity?')">
                            @csrf
                            <button type="submit" class="text-xs text-gray-500 hover:underline">Detach</button>
                        </form>
                        <form method="POST" action="{{ route('admin.3d.delete', ['assetId' => $asset->id]) }}" class="inline"
                              onsubmit="return confirm('Delete this 3D asset permanently? This cannot be undone.')">
                            @csrf
                            <button type="submit" class="text-xs text-red-500 hover:underline">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-gray-500 text-center py-8">No 3D assets yet.
                    @if(!$isInst) <a href="{{ route('admin.3d.upload') }}" class="text-kicc-red">Upload your first splat or GLB model.</a>
                    @else <a href="{{ route('admin.3d.upload.institution', ['institution' => $institution]) }}" class="text-kicc-red">Upload your first splat or GLB model.</a>
                    @endif
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{-- Room3D Table --}}
    <div class="kicc-h section-header text-lg font-bold text-gray-900 mb-4">Virtual Rooms (Room3D)</div>
    <div class="table-kicc-wrap">
        <table class="table-kicc">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Status</th>
                    <th>Pipeline</th>
                    <th>Entity</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($room3ds as $room)
                <tr>
                    <td><a href="{{ route('room3d.show', ['id' => $room->id]) }}" class="font-semibold text-gray-900 hover:text-kicc-red">{{ $room->title }}</a></td>
                    <td>
                        @if($room->status === 'ready' || $room->status === 'processed')
                        <span class="badge-kicc badge-kicc-green">{{ $room->status }}</span>
                        @elseif($room->status === 'draft')
                        <span class="badge-kicc badge-kicc-blue">Draft</span>
                        @elseif($room->status === 'failed')
                        <span class="badge-kicc badge-kicc-red">Failed</span>
                        @else
                        <span class="badge-kicc badge-kicc">{{ $room->status }}</span>
                        @endif
                    </td>
                    <td class="text-xs text-gray-500">{{ $room->pipeline }}</td>
                    <td class="text-xs text-gray-500">
                        @if($room->entity_type)
                        {{ Str::afterLast($room->entity_type, '\\Models\\') }} #{{ $room->entity_id }}
                        @else
                        <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="flex gap-2">
                        <a href="{{ route('room3d.show', ['id' => $room->id]) }}" class="text-xs text-kicc-red hover:underline">View</a>
                        <a href="{{ route('room3d.viewer', ['id' => $room->id]) }}" class="text-xs text-gray-600 hover:underline">3D View</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-gray-500 text-center py-8">No virtual rooms yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection