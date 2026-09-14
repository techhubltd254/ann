<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booth;
use App\Models\Exhibition;
use Illuminate\Http\Request;

class BoothController extends Controller
{
    public function index(Request $request)
    {
        $query = Booth::query()->with('exhibition:id,name,slug');

        if ($request->filled('exhibition_id')) {
            $query->where('exhibition_id', $request->exhibition_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('size')) {
            $query->where('size', $request->size);
        }

        $booths = $query->orderBy('booth_number')->paginate($request->per_page ?? 20);
        return response()->json($booths);
    }

    public function show(Booth $booth)
    {
        $booth->load('exhibition:id,name,slug');
        return response()->json($booth);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'exhibition_id' => 'required|exists:exhibitions,id',
            'booth_number' => 'required|string|max:50',
            'name' => 'nullable|string|max:255',
            'size' => 'nullable|string|in:small,standard,large,premium,custom',
            'category' => 'nullable|string|in:standard,premium,vip,corner,island',
            'description' => 'nullable|string',
            'amenities' => 'nullable|array',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'max_quantity' => 'nullable|integer|min:1',
            'location_hint' => 'nullable|string',
            'dimensions' => 'nullable|array',
            'images' => 'nullable|array',
            'status' => 'nullable|string|in:available,booked,reserved,maintenance',
        ]);

        $exists = Booth::where('exhibition_id', $validated['exhibition_id'])
            ->where('booth_number', $validated['booth_number'])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Booth number already exists for this exhibition'], 422);
        }

        $booth = Booth::create($validated);
        return response()->json($booth, 201);
    }

    public function update(Request $request, Booth $booth)
    {
        $validated = $request->validate([
            'booth_number' => 'sometimes|string|max:50',
            'name' => 'nullable|string|max:255',
            'size' => 'nullable|string|in:small,standard,large,premium,custom',
            'category' => 'nullable|string|in:standard,premium,vip,corner,island',
            'description' => 'nullable|string',
            'amenities' => 'nullable|array',
            'price' => 'sometimes|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'max_quantity' => 'nullable|integer|min:1',
            'booked_quantity' => 'nullable|integer|min:0',
            'location_hint' => 'nullable|string',
            'dimensions' => 'nullable|array',
            'images' => 'nullable|array',
            'status' => 'nullable|string|in:available,booked,reserved,maintenance',
        ]);

        $booth->update($validated);
        return response()->json($booth);
    }

    public function destroy(Booth $booth)
    {
        $booth->delete();

        app(\App\Services\CacheSyncService::class)->kicc();

        return response()->json(null, 204);
    }
}
