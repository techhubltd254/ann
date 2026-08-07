<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exhibition;
use Illuminate\Http\Request;

class ExhibitionController extends Controller
{
    public function index(Request $request)
    {
        $query = Exhibition::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('county_id')) {
            $query->where('county_id', $request->county_id);
        }

        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($qr) use ($q) {
                $qr->where('name', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('tagline', 'like', "%{$q}%");
            });
        }

        $exhibitions = $query->with('county', 'venue')
            ->orderBy('start_date')
            ->paginate($request->per_page ?? 20);

        return response()->json($exhibitions);
    }

    public function show(string $slug)
    {
        $exhibition = Exhibition::where('slug', $slug)
            ->with([
                'county', 'venue',
                'sessions' => fn($q) => $q->orderBy('start_time'),
                'booths' => fn($q) => $q->where('status', 'available'),
                'ticketTypes' => fn($q) => $q->where('is_active', true)->where('sale_start', '<=', now())->where(function ($qr) {
                    $qr->whereNull('sale_end')->orWhere('sale_end', '>=', now());
                }),
            ])
            ->firstOrFail();

        return response()->json($exhibition);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'tagline' => 'nullable|string|max:255',
            'county_id' => 'nullable|exists:counties,id',
            'venue_id' => 'nullable|exists:venues,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'open_time' => 'nullable|date_format:H:i',
            'close_time' => 'nullable|date_format:H:i',
            'cover_image' => 'nullable|string',
            'status' => 'nullable|string|in:draft,published,cancelled,completed',
            'is_featured' => 'nullable|boolean',
            'organizer_info' => 'nullable|array',
            'meta' => 'nullable|array',
        ]);

        $exhibition = Exhibition::create($validated);
        return response()->json($exhibition, 201);
    }

    public function update(Request $request, string $slug)
    {
        $exhibition = Exhibition::where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'tagline' => 'nullable|string|max:255',
            'county_id' => 'nullable|exists:counties,id',
            'venue_id' => 'nullable|exists:venues,id',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'open_time' => 'nullable|date_format:H:i',
            'close_time' => 'nullable|date_format:H:i',
            'cover_image' => 'nullable|string',
            'gallery' => 'nullable|array',
            'status' => 'nullable|string|in:draft,published,cancelled,completed',
            'is_featured' => 'nullable|boolean',
            'organizer_info' => 'nullable|array',
            'meta' => 'nullable|array',
        ]);

        $exhibition->update($validated);
        return response()->json($exhibition);
    }

    public function destroy(string $slug)
    {
        $exhibition = Exhibition::where('slug', $slug)->firstOrFail();
        $exhibition->delete();
        return response()->json(null, 204);
    }
}
