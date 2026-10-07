<?php

namespace App\Http\Controllers;

use App\Models\MediaAsset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class R2UploadController extends Controller
{
    public function presignedUploadUrl(Request $request): JsonResponse
    {
        abort_unless(Auth::user()?->is_admin, 403);
        $data = $request->validate([
            'path' => 'required|string|max:500',
            'mime' => 'required|string|max:100',
        ]);

        $svc = app(\App\Services\R2PresignedUploadService::class);
        $result = $svc->generateUploadPresignedUrl($data['path'], $data['mime']);
        return response()->json($result);
    }

    public function confirmR2Upload(Request $request): JsonResponse
    {
        abort_unless(Auth::user()?->is_admin, 403);
        $data = $request->validate([
            'path'          => 'required|string|max:500',
            'owner_type'    => 'required|string|max:200',
            'owner_id'      => 'required|integer',
            'slot'          => 'required|string|max:100',
            'original_name' => 'required|string|max:255',
            'mime'          => 'required|string|max:100',
            'size_bytes'    => 'required|integer|min:1',
        ]);

        // Find the Record for the given owner
        $record = \App\Models\Record::findByOwner($data['owner_type'], $data['owner_id']);
        if (!$record) {
            return response()->json(['error' => 'Record not found for owner'], 404);
        }

        // Delete old media in this slot
        MediaAsset::where('record_id', $record->id)->where('description', $data['slot'])->delete();

        $asset = MediaAsset::create([
            'record_id'     => $record->id,
            'disk'          => 'r2',
            'path'          => $data['path'],
            'original_name' => $data['original_name'],
            'mime'          => $data['mime'],
            'bytes'         => $data['size_bytes'],
            'title'         => $data['original_name'],
            'description'   => $data['slot'],
            'status'        => 'published',
            'format'        => 'standard',
        ]);

        return response()->json(['asset_id' => $asset->id, 'path' => $data['path']]);
    }
}