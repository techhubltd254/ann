<?php
namespace App\Http\Controllers\Live;

use App\Http\Controllers\Controller;
use App\Models\LiveStream;
use App\Models\Screen;
use App\Models\ScreenGroup;
use App\Models\StreamDestination;
use Illuminate\Http\Request;

class ScreenBroadcastController extends Controller
{
    public function index()
    {
        $screens = Screen::with('group', 'liveFeed')->orderBy('location')->get();
        $groups = ScreenGroup::with('screens')->orderBy('name')->get();
        $liveStreams = LiveStream::where('isLive', true)->latest()->get();
        $activeDestinations = StreamDestination::where('status', 'active')->pluck('live_stream_id', 'destinable_id')->toArray();

        $regions = $screens->groupBy('location');

        return view('live.screens.broadcast', compact('screens', 'groups', 'liveStreams', 'activeDestinations', 'regions'));
    }

    public function routeToScreen(Request $request)
    {
        $validated = $request->validate([
            'live_stream_id' => 'required|exists:live_streams,id',
            'screen_id' => 'sometimes|required_without:group_id|exists:screens,id',
            'group_id' => 'sometimes|required_without:screen_id|exists:screen_groups,id',
            'action' => 'required|in:assign,remove',
        ]);

        $targetIds = [];
        if (isset($validated['screen_id'])) {
            $targetIds[] = $validated['screen_id'];
        }
        if (isset($validated['group_id'])) {
            $group = ScreenGroup::with('screens')->find($validated['group_id']);
            $targetIds = array_merge($targetIds, $group->screens->pluck('id')->toArray());
        }

        foreach ($targetIds as $screenId) {
            $screen = Screen::find($screenId);
            if (!$screen) continue;

            if ($validated['action'] === 'assign') {
                // Update screen's live feed
                $screen->update(['live_feed_id' => $validated['live_stream_id']]);

                // Record destination
                StreamDestination::updateOrCreate(
                    ['destinable_id' => $screenId, 'destinable_type' => Screen::class],
                    [
                        'live_stream_id' => $validated['live_stream_id'],
                        'status' => 'active',
                        'started_at' => now(),
                    ]
                );
            } else {
                $screen->update(['live_feed_id' => null]);
                StreamDestination::where('destinable_id', $screenId)
                    ->where('destinable_type', Screen::class)
                    ->update(['status' => 'ended', 'ended_at' => now()]);
            }
        }

        $count = count($targetIds);
        $msg = $validated['action'] === 'assign'
            ? "Live stream routed to {$count} screen(s)"
            : "Live stream removed from {$count} screen(s)";

        return redirect()->route('live.screens.broadcast')->with('success', $msg);
    }

    public function routeToAll(Request $request)
    {
        $validated = $request->validate([
            'live_stream_id' => 'required|exists:live_streams,id',
            'action' => 'required|in:assign,remove',
        ]);

        $screens = Screen::where('active', true)->get();
        $count = 0;

        foreach ($screens as $screen) {
            if ($validated['action'] === 'assign') {
                $screen->update(['live_feed_id' => $validated['live_stream_id']]);
                StreamDestination::updateOrCreate(
                    ['destinable_id' => $screen->id, 'destinable_type' => Screen::class],
                    ['live_stream_id' => $validated['live_stream_id'], 'status' => 'active', 'started_at' => now()]
                );
                $count++;
            } else {
                $screen->update(['live_feed_id' => null]);
                StreamDestination::where('destinable_id', $screen->id)
                    ->where('destinable_type', Screen::class)
                    ->update(['status' => 'ended', 'ended_at' => now()]);
                $count++;
            }
        }

        $msg = $validated['action'] === 'assign'
            ? "Live stream broadcast to ALL {$count} screens"
            : "Live stream removed from ALL {$count} screens";

        return redirect()->route('live.screens.broadcast')->with('success', $msg);
    }

    public function routeToScreenByName(Request $request)
    {
        // Quick route to known screen venues
        $validated = $request->validate([
            'live_stream_id' => 'required|exists:live_streams,id',
            'venue' => 'required|string', // 'kicc-nairobi', 'elite-sounds', 'digital-mara', 'town-hall'
            'action' => 'required|in:assign,remove',
        ]);

        $venueKeywords = [
            'kicc-nairobi' => '%KICC%Nairobi%',
            'elite-sounds' => '%Elite%Sounds%',
            'digital-mara' => '%Digital%Mara%',
            'town-hall' => '%Town%Hall%President%',
        ];

        $keyword = $venueKeywords[$validated['venue']] ?? null;
        if (!$keyword) {
            return redirect()->route('live.screens.broadcast')->with('error', 'Unknown venue');
        }

        $screens = Screen::where('label', 'like', $keyword)->orWhere('location', 'like', $keyword)->get();
        $count = 0;
        foreach ($screens as $screen) {
            $screen->update(['live_feed_id' => $validated['action'] === 'assign' ? $validated['live_stream_id'] : null]);
            StreamDestination::updateOrCreate(
                ['destinable_id' => $screen->id, 'destinable_type' => Screen::class],
                ['live_stream_id' => $validated['live_stream_id'], 'status' => $validated['action'] === 'assign' ? 'active' : 'ended', 'started_at' => now()]
            );
            $count++;
        }

        return redirect()->route('live.screens.broadcast')->with('success', "{$validated['venue']}: {$count} screens updated");
    }
}