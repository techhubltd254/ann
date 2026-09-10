<?php
namespace App\Http\Controllers\Live;

use App\Http\Controllers\Controller;
use App\Models\Booth;
use App\Models\MeetingBooking;
use App\Models\FavouriteBooth;
use App\Services\HeartbeatService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class HeartbeatController extends Controller
{
    protected HeartbeatService $heartbeat;

    public function __construct(HeartbeatService $heartbeat)
    {
        $this->heartbeat = $heartbeat;
    }

    public function ping(Request $request)
    {
        $validated = $request->validate([
            'booth_id' => 'required|integer|exists:booths,id',
            'session_id' => 'required|string|max:128',
            'payload' => 'nullable|array',
        ]);

        $result = $this->heartbeat->processHeartbeat(
            $validated['booth_id'],
            $validated['session_id'],
            $validated['payload'] ?? []
        );

        if (!$result) {
            return response()->json(['authorized' => false, 'status' => 'slate'], 200);
        }

        return response()->json($result);
    }

    public function bookMeeting(Request $request)
    {
        $validated = $request->validate([
            'booth_id' => 'required|exists:booths,id',
            'slot_start' => 'required|date',
            'slot_end' => 'required|date|after:slot_start',
            'visitor_name' => 'required|string|max:255',
            'visitor_email' => 'required|email|max:255',
            'visitor_phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:1000',
        ]);

        $booth = Booth::findOrFail($validated['booth_id']);

        $booking = MeetingBooking::create([
            'booth_id' => $booth->id,
            'exhibitor_user_id' => $booth->user_id,
            'visitor_user_id' => auth()->id(),
            'visitor_name' => $validated['visitor_name'],
            'visitor_email' => $validated['visitor_email'],
            'visitor_phone' => $validated['visitor_phone'] ?? null,
            'slot_start' => $validated['slot_start'],
            'slot_end' => $validated['slot_end'],
            'notes' => $validated['notes'] ?? null,
            'calendar_token' => Str::random(32),
        ]);

        return response()->json(['booking_id' => $booking->id, 'status' => 'pending']);
    }

    public function toggleFavourite(Request $request)
    {
        $validated = $request->validate([
            'booth_id' => 'required|exists:booths,id',
        ]);

        $existing = FavouriteBooth::where('user_id', auth()->id())
            ->where('booth_id', $validated['booth_id'])
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json(['favourited' => false]);
        }

        FavouriteBooth::create([
            'user_id' => auth()->id(),
            'booth_id' => $validated['booth_id'],
        ]);

        return response()->json(['favourited' => true]);
    }

    public function activeBooths()
    {
        $booths = Booth::with('authorization', 'liveStreams')
            ->where('stream_status', 'live')
            ->whereHas('authorization', fn($q) => $q->where('status', 'AUTHORIZED'))
            ->get()
            ->map(fn($b) => [
                'id' => $b->id,
                'name' => $b->name,
                'slug' => $b->slug,
                'thumbnail' => $b->thumbnail,
                'stream_url' => $b->liveStreams->first()?->hls_url,
                'viewer_count' => $b->liveStreams->first()?->viewer_count ?? 0,
            ]);

        return response()->json($booths);
    }
}