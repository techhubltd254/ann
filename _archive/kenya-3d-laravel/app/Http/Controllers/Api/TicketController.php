<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $query = Ticket::with('ticketType', 'exhibition')
            ->where('user_id', $request->user()->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $tickets = $query->orderBy('created_at', 'desc')->paginate($request->per_page ?? 20);
        return response()->json($tickets);
    }

    public function show(Ticket $ticket)
    {
        $ticket->load(['ticketType', 'booking', 'exhibition']);
        return response()->json($ticket);
    }

    public function checkIn(Request $request, string $ticketCode)
    {
        $ticket = Ticket::where('ticket_code', $ticketCode)->firstOrFail();

        if ($ticket->status !== 'active') {
            return response()->json(['message' => 'Ticket is not active', 'status' => $ticket->status], 422);
        }

        if ($ticket->checked_in_at) {
            return response()->json(['message' => 'Ticket already checked in', 'checked_in_at' => $ticket->checked_in_at], 422);
        }

        $ticket->update([
            'checked_in_at' => now(),
            'check_in_data' => $request->only(['device', 'location', 'scanned_by']),
        ]);

        return response()->json($ticket);
    }

    public function lookup(string $ticketCode)
    {
        $ticket = Ticket::where('ticket_code', $ticketCode)
            ->with(['ticketType', 'booking', 'exhibition'])
            ->firstOrFail();

        return response()->json($ticket);
    }
}
