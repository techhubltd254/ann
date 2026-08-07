<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PipelineController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'video' => 'required|file|mimes:mp4,mov,avi,mkv|max:2048',
            'pipeline' => 'nullable|in:sbs,splat,hybrid',
            'depth_strength' => 'nullable|numeric|min:0.5|max:3.0',
            'convergence' => 'nullable|numeric|min:0.0|max:1.0',
            'callback_url' => 'nullable|url',
        ]);

        $video = $request->file('video');
        $path = $video->store('uploads', 'local');

        $payload = [
            'user_id' => $request->user()?->id,
            'video_path' => $path,
            'pipeline' => $request->input('pipeline', 'sbs'),
            'depth_strength' => $request->float('depth_strength', 1.5),
            'convergence' => $request->float('convergence', 0.3),
            'callback_url' => $request->input('callback_url'),
            'status' => 'queued',
        ];

        Log::info('Pipeline job queued', $payload);

        return response()->json([
            'message' => 'Video uploaded and queued for processing',
            'job' => $payload,
        ], 201);
    }

    public function status(Request $request, string $jobId)
    {
        return response()->json([
            'job_id' => $jobId,
            'status' => 'processing',
        ]);
    }
}
