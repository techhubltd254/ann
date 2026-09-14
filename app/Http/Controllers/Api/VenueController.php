<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Venue;
use Illuminate\Http\Request;

class VenueController extends Controller
{
    public function index(Request $request)
    {
        $query = Venue::where('is_active', true);

        if ($request->filled('city')) {
            $query->where('city', $request->city);
        }

        if ($request->filled('venue_type')) {
            $query->where('venue_type', $request->venue_type);
        }

        $venues = $query->orderBy('name')->paginate($request->per_page ?? 20);
        return response()->json($venues);
    }

    public function show(string $slug)
    {
        $venue = Venue::where('slug', $slug)
            ->with('exhibitions')
            ->firstOrFail();

        return response()->json($venue);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'venue_type' => 'nullable|string|in:hall,outdoor,conference,hybrid',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'county' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'capacity' => 'nullable|integer',
            'amenities' => 'nullable|array',
            'contact_info' => 'nullable|array',
            'cover_image' => 'nullable|string',
        ]);

        $venue = Venue::create($validated);
        return response()->json($venue, 201);
    }

    public function update(Request $request, string $slug)
    {
        $venue = Venue::where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'venue_type' => 'nullable|string|in:hall,outdoor,conference,hybrid',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'county' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'capacity' => 'nullable|integer',
            'amenities' => 'nullable|array',
            'contact_info' => 'nullable|array',
            'cover_image' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $venue->update($validated);
        return response()->json($venue);
    }

    public function destroy(string $slug)
    {
        $venue = Venue::where('slug', $slug)->firstOrFail();
        $venue->delete();

        app(\App\Services\CacheSyncService::class)->kicc();

        return response()->json(null, 204);
    }
}
