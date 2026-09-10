<?php
namespace App\Http\Controllers\Live;

use App\Http\Controllers\Controller;
use App\Models\Booth;
use App\Models\BoothAuthorization;
use App\Models\ExhibitorStudioSession;
use App\Models\LiveStream;
use App\Services\CloudflareStreamService;
use App\Services\HeartbeatService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ExhibitorStudioController extends Controller
{
    protected HeartbeatService $heartbeat;
    protected CloudflareStreamService $cloudflare;

    public function __construct(HeartbeatService $heartbeat, CloudflareStreamService $cloudflare)
    {
        $this->heartbeat = $heartbeat;
        $this->cloudflare = $cloudflare;
        $this->middleware('auth');
    }

    public function index(Booth $booth)
    {
        $auth = $booth->authorization;
        if (!$auth || $auth->status !== 'AUTHORIZED') {
            return view('live.exhibitor.lockout', ['booth' => $booth, 'reason' => 'unauthorized']);
        }

        $session = ExhibitorStudioSession::firstOrCreate(
            ['user_id' => auth()->id(), 'booth_id' => $booth->id],
            [
                'booth_authorization_id' => $auth->id,
                'session_token' => Str::random(64),
                'stream_status' => 'offline',
                'session_started_at' => now(),
                'last_activity_at' => now(),
                'expires_at' => now()->addHours(12),
            ]
        );

        $activeStream = LiveStream::where('booth_id', $booth->id)->where('isLive', true)->latest()->first();
        return view('live.exhibitor.studio', compact('booth', 'auth', 'session', 'activeStream'));
    }

    public function goLive(Booth $booth, Request $request)
    {
        $auth = $booth->authorization;
        if (!$auth || $auth->status !== 'AUTHORIZED') {
            return response()->json(['error' => 'Not authorized'], 403);
        }

        // Create Cloudflare Stream live input
        $liveInput = $this->cloudflare->createLiveInput($booth->name . ' - ' . now()->toDayDateTimeString());
        if (!$liveInput || !isset($liveInput['uid'])) {
            return response()->json(['error' => 'Failed to create live stream input'], 500);
        }

        $stream = LiveStream::create([
            'booth_id' => $booth->id,
            'user_id' => auth()->id(),
            'title' => $booth->name . ' Live',
            'stream_url' => $liveInput['rtmps']['url'] ?? $liveInput['rtmp']['url'] ?? null,
            'stream_key' => $liveInput['rtmps']['streamKey'] ?? $liveInput['rtmp']['streamKey'] ?? null,
            'hls_url' => $liveInput['preview'] ?? $this->cloudflare->getHlsUrl($liveInput['uid']),
            'playback_url' => $liveInput['uid'] ? "https://cloudflarestream.com/{$liveInput['uid']}/manifest/video.m3u8" : null,
            'cloudflare_uid' => $liveInput['uid'],
            'isLive' => true,
            'started_at' => now(),
        ]);

        $session = ExhibitorStudioSession::where('booth_id', $booth->id)
            ->where('user_id', auth()->id())->first();
        if ($session) $session->setStreamStatus('live');
        $booth->update(['stream_status' => 'live']);

        return response()->json([
            'status' => 'live',
            'booth_id' => $booth->id,
            'stream_id' => $stream->id,
            'rtmp_url' => $stream->stream_url,
            'stream_key' => $stream->stream_key,
            'hls_url' => $stream->hls_url,
        ]);
    }

    public function endStream(Booth $booth)
    {
        LiveStream::where('booth_id', $booth->id)->where('isLive', true)->update(['isLive' => false, 'ended_at' => now()]);
        ExhibitorStudioSession::where('booth_id', $booth->id)->where('user_id', auth()->id())->update(['stream_status' => 'offline']);
        $booth->update(['stream_status' => 'offline']);
        return response()->json(['status' => 'offline']);
    }

    public function updateBooth(Booth $booth, Request $request)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:5000',
            'contact_phone' => 'sometimes|string|max:20',
            'contact_email' => 'sometimes|email|max:255',
            'whatsapp' => 'sometimes|string|max:20',
            'gps_lat' => 'sometimes|numeric',
            'gps_lng' => 'sometimes|numeric',
            'physical_address' => 'sometimes|string|max:500',
            'meeting_slots' => 'sometimes|json',
        ]);
        $booth->update($validated);
        return redirect()->back()->with('success', 'Booth updated.');
    }

    public function signedStreamUrl(Booth $booth)
    {
        if (!$booth->user_id === auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $stream = LiveStream::where('booth_id', $booth->id)->where('isLive', true)->first();
        if (!$stream || !$stream->cloudflare_uid) {
            return response()->json(['error' => 'No active stream'], 404);
        }
        $signed = app(\App\Services\SignedUrlService::class)->signStreamManifest($stream->cloudflare_uid);
        return response()->json(['signed_url' => $signed, 'expires_in' => 900]);
    }
}