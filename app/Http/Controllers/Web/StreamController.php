<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\LiveStream;
use App\Models\Exhibition;
use App\Services\CloudflareStreamService;
use Illuminate\Http\Request;

class StreamController extends Controller
{
    public function index(Request $request)
    {
        $live = LiveStream::with('exhibition', 'county', 'user')
            ->where('status', 'live')
            ->latest()
            ->take(10)
            ->get();

        $upcoming = LiveStream::with('exhibition', 'county', 'user')
            ->where('status', 'idle')
            ->latest()
            ->take(10)
            ->get();

        $ended = LiveStream::with('exhibition', 'county', 'user')
            ->where('status', 'ended')
            ->latest()
            ->take(6)
            ->get();

        $upcomingExhibitions = Exhibition::with('county')
            ->withCount('booths')
            ->where('status', 'published')
            ->where('start_date', '>=', now())
            ->orderBy('start_date')
            ->take(6)
            ->get();

        return view('streams.index', compact('live', 'upcoming', 'ended', 'upcomingExhibitions'));
    }

    public function show(LiveStream $stream)
    {
        $stream->load('exhibition', 'county', 'user');
        return view('streams.show', compact('stream'));
    }

    public function create()
    {
        $exhibitions = Exhibition::where('status', 'published')->orderBy('start_date', 'desc')->get();
        return view('streams.create', compact('exhibitions'));
    }

    public function store(Request $request, CloudflareStreamService $cf)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'exhibition_id' => 'nullable|exists:exhibitions,id',
            'county_id' => 'nullable|exists:counties,id',
        ]);

        $data['user_id'] = $request->user()?->id;
        $data['status'] = 'idle';

        $stream = LiveStream::create($data);

        // Create a Cloudflare live input so we get an RTMPS URL
        $input = $cf->generateLiveInput($stream->name);
        if ($input) {
            $stream->update([
                'stream_url' => $input['rtmps_url'] ?? $input['rtmp_url'] ?? null,
                'playback_url' => $input['playback']['hls'] ?? null,
                'hls_url' => $input['playback']['hls'] ?? null,
            ]);
        }

        return redirect()->route('streams.show', $stream)
            ->with('success', 'Stream created. Share the RTMPS URL with your broadcaster.');
    }

    public function goLive(Request $request, LiveStream $stream, CloudflareStreamService $cf)
    {
        if (!$stream->stream_url) {
            $input = $cf->generateLiveInput($stream->name);
            if ($input) {
                $stream->update([
                    'stream_url' => $input['rtmps_url'] ?? $input['rtmp_url'] ?? null,
                    'playback_url' => $input['playback']['hls'] ?? null,
                    'hls_url' => $input['playback']['hls'] ?? null,
                ]);
            }
        }
        $stream->update(['status' => 'live', 'started_at' => now()]);
        return back()->with('success', 'Stream is now live!');
    }

    public function endStream(Request $request, LiveStream $stream)
    {
        $stream->update(['status' => 'ended', 'ended_at' => now()]);
        return back()->with('success', 'Stream ended.');
    }

    public function destroy(LiveStream $stream)
    {
        $stream->delete();
        return redirect()->route('streams.index')->with('success', 'Stream deleted.');
    }

    public function update(Request $request, LiveStream $stream)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'exhibition_id' => 'nullable|exists:exhibitions,id',
            'county_id' => 'nullable|exists:counties,id',
            'status' => 'nullable|in:idle,live,ended',
            'thumbnail_url' => 'nullable|string|max:500',
            'hls_url' => 'nullable|string|max:500',
        ]);

        if (isset($data['status']) && $data['status'] === 'live' && $stream->status !== 'live') {
            $data['started_at'] = now();
        }
        if (isset($data['status']) && $data['status'] === 'ended' && $stream->status !== 'ended') {
            $data['ended_at'] = now();
        }

        $stream->update($data);
        return back()->with('success', 'Stream updated.');
    }

    public function adminIndex(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasAnyRole(['kicc_admin', 'national_admin', 'county_admin', 'institution_admin']), 403);

        $query = LiveStream::with('exhibition', 'county', 'user');

        if ($user->hasRole('county_admin') && $user->county_id) {
            $query->where('county_id', $user->county_id);
        }

        $streams = $query->latest()->paginate(20);
        $exhibitions = Exhibition::where('status', 'published')->orderBy('start_date', 'desc')->get();
        $counties = \App\Models\County::orderBy('name')->get(['id', 'name', 'slug']);

        return view('streams.admin', compact('streams', 'exhibitions', 'counties'));
    }

    public function setThumbnail(Request $request, LiveStream $stream, CloudflareStreamService $cf)
    {
        $data = $request->validate([
            'thumbnail_url' => 'nullable|string|max:500',
            'hls_url' => 'nullable|string|max:500',
        ]);
        $stream->update(array_filter($data));
        return back()->with('success', 'Stream media updated.');
    }

    public function apiLiveStreams()
    {
        $streams = LiveStream::with('exhibition', 'county')
            ->where('status', 'live')
            ->latest()
            ->take(10)
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'status' => $s->status,
                'hls_url' => $s->hls_url,
                'thumbnail_url' => $s->thumbnail_url,
                'viewer_count' => $s->viewer_count,
                'exhibition' => $s->exhibition?->name,
                'county' => $s->county?->name,
            ]);
        return response()->json($streams);
    }

    public function apiChatMessages(LiveStream $stream)
    {
        $messages = \App\Models\ChatMessage::where('live_stream_id', $stream->id)
            ->latest()
            ->take(50)
            ->get()
            ->reverse()
            ->values();
        return response()->json($messages);
    }

    public function apiPostChat(Request $request, LiveStream $stream)
    {
        $data = $request->validate(['message' => 'required|string|max:500']);
        $msg = \App\Models\ChatMessage::create([
            'live_stream_id' => $stream->id,
            'user_id' => $request->user()?->id ?? 0,
            'user_name' => $request->user()?->name ?? 'Guest',
            'message' => $data['message'],
        ]);
        // Increment viewer count slightly
        $stream->increment('viewer_count');
        return response()->json($msg);
    }
}