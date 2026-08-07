<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Booth;
use App\Models\Ticket;
use App\Models\TicketType;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $query = Booking::with('exhibition:id,name,slug')
            ->where('user_id', $request->user()->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $bookings = $query->orderBy('created_at', 'desc')->paginate($request->per_page ?? 20);
        return response()->json($bookings);
    }

    public function show(Booking $booking)
    {
        $booking->load(['exhibition', 'bookingBooths.booth', 'tickets.ticketType']);
        return response()->json($booking);
    }

    public function storeBoothBooking(Request $request)
    {
        $validated = $request->validate([
            'exhibition_id' => 'required|exists:exhibitions,id',
            'booths' => 'required|array|min:1',
            'booths.*.booth_id' => 'required|exists:booths,id',
            'booths.*.exhibitor_name' => 'nullable|string|max:255',
            'booths.*.exhibitor_email' => 'nullable|email|max:255',
            'booths.*.exhibitor_phone' => 'nullable|string|max:20',
            'booths.*.requirements' => 'nullable|array',
            'billing_info' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        $user = $request->user();
        $boothIds = collect($validated['booths'])->pluck('booth_id');
        $booths = Booth::whereIn('id', $boothIds)->where('exhibition_id', $validated['exhibition_id'])->get();

        if ($booths->count() !== $boothIds->count()) {
            return response()->json(['message' => 'One or more booths not found in this exhibition'], 422);
        }

        $unavailable = $booths->filter(fn($b) => $b->status !== 'available');
        if ($unavailable->isNotEmpty()) {
            return response()->json([
                'message' => 'Some booths are no longer available',
                'booths' => $unavailable->pluck('booth_number'),
            ], 422);
        }

        $subtotal = $booths->sum('price');
        $total = $subtotal;

        $booking = Booking::create([
            'booking_reference' => 'BKN-' . strtoupper(Str::random(12)),
            'user_id' => $user->id,
            'exhibition_id' => $validated['exhibition_id'],
            'booking_type' => 'booth',
            'subtotal' => $subtotal,
            'tax' => 0,
            'total' => $total,
            'currency' => 'KES',
            'status' => 'pending',
            'billing_info' => $validated['billing_info'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        foreach ($validated['booths'] as $item) {
            $booth = $booths->firstWhere('id', $item['booth_id']);
            $booking->bookingBooths()->create([
                'booth_id' => $booth->id,
                'price' => $booth->price,
                'discount' => 0,
                'exhibitor_name' => $item['exhibitor_name'] ?? null,
                'exhibitor_email' => $item['exhibitor_email'] ?? null,
                'exhibitor_phone' => $item['exhibitor_phone'] ?? null,
                'requirements' => $item['requirements'] ?? null,
            ]);

            $booth->increment('booked_quantity');
            $booth->status = $booth->booked_quantity >= $booth->max_quantity ? 'booked' : 'reserved';
            $booth->save();
        }

        return response()->json($booking, 201);
    }

    public function storeTicketBooking(Request $request)
    {
        $validated = $request->validate([
            'exhibition_id' => 'required|exists:exhibitions,id',
            'tickets' => 'required|array|min:1',
            'tickets.*.ticket_type_id' => 'required|exists:ticket_types,id',
            'tickets.*.quantity' => 'required|integer|min:1',
            'tickets.*.holder_name' => 'nullable|string|max:255',
            'tickets.*.holder_email' => 'nullable|email|max:255',
            'billing_info' => 'nullable|array',
        ]);

        $user = $request->user();
        $items = [];

        foreach ($validated['tickets'] as $item) {
            $ticketType = TicketType::where('id', $item['ticket_type_id'])
                ->where('exhibition_id', $validated['exhibition_id'])
                ->where('is_active', true)
                ->firstOrFail();

            $quantity = min($item['quantity'], $ticketType->max_per_order);
            $available = $ticketType->quantity - $ticketType->sold;

            if ($quantity > $available) {
                return response()->json([
                    'message' => "Not enough tickets available for {$ticketType->name}",
                    'available' => $available,
                ], 422);
            }

            $items[] = [
                'type' => $ticketType,
                'quantity' => $quantity,
                'price' => $ticketType->discount_price ?? $ticketType->price,
                'holder_name' => $item['holder_name'] ?? null,
                'holder_email' => $item['holder_email'] ?? null,
            ];
        }

        $subtotal = collect($items)->sum(fn($i) => $i['price'] * $i['quantity']);
        $total = $subtotal;

        $booking = Booking::create([
            'booking_reference' => 'TKT-' . strtoupper(Str::random(12)),
            'user_id' => $user->id,
            'exhibition_id' => $validated['exhibition_id'],
            'booking_type' => 'ticket',
            'subtotal' => $subtotal,
            'tax' => 0,
            'total' => $total,
            'currency' => 'KES',
            'status' => 'pending',
            'billing_info' => $validated['billing_info'] ?? null,
        ]);

        foreach ($items as $item) {
            for ($i = 0; $i < $item['quantity']; $i++) {
                $ticket = Ticket::create([
                    'ticket_code' => strtoupper(Str::random(16)),
                    'booking_id' => $booking->id,
                    'ticket_type_id' => $item['type']->id,
                    'user_id' => $user->id,
                    'price' => $item['price'],
                    'status' => 'active',
                    'holder_name' => $item['holder_name'],
                    'holder_email' => $item['holder_email'],
                ]);

                $ticket->qr_code = 'QR-' . $ticket->ticket_code . '-' . $ticket->id;
                $ticket->save();
            }

            $item['type']->increment('sold', $item['quantity']);
        }

        return response()->json($booking, 201);
    }

    public function cancel(Booking $booking)
    {
        if (!in_array($booking->status, ['pending', 'confirmed'])) {
            return response()->json(['message' => 'Booking cannot be cancelled'], 422);
        }

        $booking->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        foreach ($booking->bookingBooths as $bb) {
            $bb->booth->decrement('booked_quantity');
            if ($bb->booth->status === 'booked' && $bb->booth->booked_quantity < $bb->booth->max_quantity) {
                $bb->booth->status = 'reserved';
                $bb->booth->save();
            }
        }

        $booking->tickets()->each(function ($ticket) {
            $ticket->ticketType->decrement('sold');
            $ticket->update(['status' => 'cancelled', 'cancelled_at' => now()]);
        });

        return response()->json($booking);
    }
}
