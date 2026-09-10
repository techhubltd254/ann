<?php
namespace App\Http\Controllers\Live;

use App\Http\Controllers\Controller;
use App\Models\Booth;
use App\Models\BoothAuthorization;
use App\Models\ExhibitorStudioSession;
use App\Services\HeartbeatService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ExhibitorStudioController extends Controller
{
    protected HeartbeatService $heartbeat;

    public function __construct(HeartbeatService $heartbeat)
    {
        $this->heartbeat = $heartbeat;
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

        return view('live.exhibitor.studio', compact('booth', 'auth', 'session'));
    }

    public function goLive(Booth $booth, Request $request)
    {
        $auth = $booth->authorization;
        if (!$auth || $auth->status !== 'AUTHORIZED') {
            return response()->json(['error' => 'Not authorized'], 403);
        }

        $session = ExhibitorStudioSession::where('booth_id', $booth->id)
            ->where('user_id', auth()->id())
            ->first();

        if ($session) {
            $session->setStreamStatus('live');
        }

        $booth->update(['stream_status' => 'live']);

        return response()->json(['status' => 'live', 'booth_id' => $booth->id]);
    }

    public function endStream(Booth $booth)
    {
        ExhibitorStudioSession::where('booth_id', $booth->id)
            ->where('user_id', auth()->id())
            ->update(['stream_status' => 'offline']);

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
}