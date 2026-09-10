<?php
namespace App\Http\Controllers\Live;

use App\Http\Controllers\Controller;
use App\Models\Booth;
use App\Models\BoothAuthorization;
use App\Services\HeartbeatService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BoothController extends Controller
{
    public function index()
    {
        $booths = Booth::with('authorization', 'liveStreams', 'user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('live.booths.index', compact('booths'));
    }

    public function show(Booth $booth)
    {
        $booth->load('authorization', 'liveStreams', 'meetingBookings');
        $health = app(HeartbeatService::class)->getHeartbeatHealth($booth->id);

        return view('live.booths.show', compact('booth', 'health'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'exhibition_id' => 'nullable|exists:exhibitions,id',
            'county_id' => 'nullable|exists:counties,id',
            'contact_phone' => 'nullable|string|max:20',
            'contact_email' => 'nullable|email',
            'gps_lat' => 'nullable|numeric',
            'gps_lng' => 'nullable|numeric',
        ]);

        $validated['slug'] = Str::slug($validated['name']) . '-' . Str::random(6);
        $validated['user_id'] = auth()->id();
        $validated['status'] = 'pending';

        $booth = Booth::create($validated);

        return redirect()->route('live.booths.show', $booth)
            ->with('success', 'Booth created. Awaiting authorization.');
    }
}