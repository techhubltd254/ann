<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Venue;
use App\Events\GenericDomainEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VenueController extends Controller
{
    public function show(Venue $venue)
    {
        $venue->load('exhibitions');

        $upcomingExhibitions = $venue->exhibitions()
            ->where('status', 'published')
            ->where('start_date', '>=', now())
            ->orderBy('start_date')
            ->take(6)
            ->get();

        return view('venues.show', compact('venue', 'upcomingExhibitions'));
    }

    public function inquire(Request $request, Venue $venue)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:30',
            'event_type' => 'required|string|max:60',
            'guests' => 'nullable|integer|min:1|max:100000',
            'event_date' => 'required|date|after:today',
            'message' => 'nullable|string|max:2000',
        ]);

        Log::info('Venue booking inquiry', [
            'venue' => $venue->name,
            'venue_id' => $venue->id,
            ...$validated,
        ]);

        event(new GenericDomainEvent('venue_inquiry', [
            'venue' => $venue->name, 'venue_id' => $venue->id,
            'event_type' => $validated['event_type'], 'event_date' => $validated['event_date'],
        ], n8nEventName: 'venue_inquiry'));;
        return back()->with('success', "Inquiry sent! Our events team will contact you about {$venue->name} within 24 hours.");
    }
}
