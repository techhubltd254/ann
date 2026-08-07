<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TicketType;
use Illuminate\Http\Request;

class TicketTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = TicketType::query()->with('exhibition:id,name,slug');

        if ($request->filled('exhibition_id')) {
            $query->where('exhibition_id', $request->exhibition_id);
        }

        if ($request->boolean('active_only')) {
            $query->where('is_active', true);
        }

        $types = $query->orderBy('sort_order')->paginate($request->per_page ?? 20);
        return response()->json($types);
    }

    public function show(TicketType $ticketType)
    {
        $ticketType->load('exhibition:id,name,slug');
        return response()->json($ticketType);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'exhibition_id' => 'required|exists:exhibitions,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'quantity' => 'required|integer|min:0',
            'max_per_order' => 'nullable|integer|min:1',
            'sale_start' => 'nullable|date',
            'sale_end' => 'nullable|date|after:sale_start',
            'benefits' => 'nullable|array',
            'color' => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $type = TicketType::create($validated);
        return response()->json($type, 201);
    }

    public function update(Request $request, TicketType $ticketType)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'quantity' => 'sometimes|integer|min:0',
            'max_per_order' => 'nullable|integer|min:1',
            'sale_start' => 'nullable|date',
            'sale_end' => 'nullable|date|after:sale_start',
            'benefits' => 'nullable|array',
            'color' => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $ticketType->update($validated);
        return response()->json($ticketType);
    }

    public function destroy(TicketType $ticketType)
    {
        if ($ticketType->sold > 0) {
            return response()->json(['message' => 'Cannot delete a ticket type with sales'], 422);
        }
        $ticketType->delete();
        return response()->json(null, 204);
    }
}
