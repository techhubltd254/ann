<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Venue;
use App\Models\VenueBooking;
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

        return view('experience.pages.venues.show', compact('venue', 'upcomingExhibitions'));
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
        ], n8nEventName: 'venue_inquiry'));

        return back()->with('success', "Inquiry sent! Our events team will contact you about {$venue->name} within 24 hours.");
    }

    /** GET /venues/{venue}/book — the reservation form. */
    public function book(Venue $venue)
    {
        return view('experience.pages.venues.book', [
            'venue' => $venue,
            'quote' => null,
            'quoteInput' => [],
        ]);
    }

    /**
     * POST /venues/{venue}/quote — price the requested hire from the venue's own rates.
     * Rates live on the venue row (conference_rate / exhibition_rate / concert_rate),
     * so nothing here is hardcoded per venue.
     */
    public function quote(Request $request, Venue $venue)
    {
        $data = $this->validateQuote($request);

        $quote = $this->priceQuote($venue, $data);

        return view('experience.pages.venues.book', [
            'venue' => $venue,
            'quote' => $quote,
            'quoteInput' => $data,
        ]);
    }

    /** POST /venues/{venue}/reserve — persist a reservation request. */
    public function reserve(Request $request, Venue $venue)
    {
        $data = $this->validateQuote($request);

        $quote = $this->priceQuote($venue, $data);

        $booking = VenueBooking::create([
            'venue_id' => $venue->id,
            'user_id' => $request->user()?->id,
            'event_type' => $data['event_type'],
            'event_date' => $data['event_date'],
            'event_end_date' => $data['event_end_date'] ?? $data['event_date'],
            // Both columns are NOT NULL in the schema (cars defaults to 0), so an
            // absent value must be written as 0 — never as an explicit null.
            'expected_guests' => (int) ($data['expected_guests'] ?? 0),
            'expected_cars' => (int) ($data['expected_cars'] ?? 0),
            'security_level' => $data['security_level'] ?? null,
            'additional_facilities' => $data['additional_facilities'] ?? null,
            'catering_requirements' => $data['catering_requirements'] ?? null,
            'av_requirements' => $data['av_requirements'] ?? null,
            'preferred_layout' => $data['preferred_layout'] ?? null,
            'requires_live_coverage' => $request->boolean('requires_live_coverage'),
            'requires_ad_screens' => $request->boolean('requires_ad_screens'),
            'requires_fountains' => $request->boolean('requires_fountains'),
            'special_requests' => $data['special_requests'] ?? null,
            'estimated_budget' => $data['estimated_budget'] ?? null,
            'total_quote' => $quote['total'],
            'deposit_amount' => $quote['deposit'],
            'status' => 'reserved',
        ]);

        event(new GenericDomainEvent('venue_reserved', [
            'venue' => $venue->name,
            'venue_id' => $venue->id,
            'booking_reference' => $booking->booking_reference,
            'event_type' => $booking->event_type,
            'event_date' => (string) $booking->event_date,
            'total_quote' => $booking->total_quote,
        ], n8nEventName: 'venue_reserved'));

        return redirect()->route('venues.booking.confirm', [$venue->slug, $booking->id]);
    }

    /** GET /venues/{venue}/booking/{booking}/confirm */
    public function confirm(Venue $venue, int $booking)
    {
        $record = VenueBooking::where('venue_id', $venue->id)->findOrFail($booking);

        return view('experience.pages.venues.booking-confirm', [
            'venue' => $venue,
            'booking' => $record,
        ]);
    }

    /** POST /venues/{venue}/booking/{booking}/pay — record the deposit payment. */
    public function payDeposit(Request $request, Venue $venue, int $booking)
    {
        $record = VenueBooking::where('venue_id', $venue->id)->findOrFail($booking);

        $request->validate([
            'payment_reference' => 'nullable|string|max:120',
        ]);

        $record->forceFill([
            'deposit_paid_at' => now(),
            'confirmed_at' => now(),
            'status' => 'confirmed',
        ])->save();

        Log::info('Venue deposit recorded', [
            'venue_id' => $venue->id,
            'booking_reference' => $record->booking_reference,
            'payment_reference' => $request->input('payment_reference'),
            'deposit_amount' => $record->deposit_amount,
        ]);

        event(new GenericDomainEvent('venue_deposit_paid', [
            'venue' => $venue->name,
            'venue_id' => $venue->id,
            'booking_reference' => $record->booking_reference,
            'deposit_amount' => $record->deposit_amount,
        ], n8nEventName: 'venue_deposit_paid'));

        return redirect()->route('venues.booking.confirm', [$venue->slug, $record->id])
            ->with('success', 'Deposit recorded — your reservation is confirmed.');
    }

    /** GET /my-venues */
    public function bookingHistory(Request $request)
    {
        $bookings = VenueBooking::with('venue')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(15);

        return view('experience.pages.venues.my-bookings', compact('bookings'));
    }

    // ─────────────────────────── internals ───────────────────────────

    protected function validateQuote(Request $request): array
    {
        return $request->validate([
            'event_type' => 'required|string|max:60',
            'event_date' => 'required|date|after:today',
            'event_end_date' => 'nullable|date|after_or_equal:event_date',
            'expected_guests' => 'nullable|integer|min:1|max:100000',
            'expected_cars' => 'nullable|integer|min:0|max:10000',
            'security_level' => 'nullable|string|max:60',
            'additional_facilities' => 'nullable|string|max:1000',
            'catering_requirements' => 'nullable|string|max:1000',
            'av_requirements' => 'nullable|string|max:1000',
            'preferred_layout' => 'nullable|string|max:120',
            'special_requests' => 'nullable|string|max:2000',
            'estimated_budget' => 'nullable|numeric|min:0',
            'days' => 'nullable|integer|min:1|max:60',
        ]);
    }

    /**
     * Derive a quote from the venue's stored rates. Returns null rate when the
     * venue has no rate for the requested event type, so the UI can say
     * "on request" instead of inventing a number.
     */
    protected function priceQuote(Venue $venue, array $data): array
    {
        $days = (int) ($data['days'] ?? 1);

        $rate = match ($data['event_type']) {
            'conference', 'meeting', 'workshop' => $venue->conference_rate,
            'exhibition', 'expo', 'trade_show' => $venue->exhibition_rate,
            'concert', 'show', 'performance' => $venue->concert_rate,
            default => null,
        };

        $rate = $rate !== null ? (float) $rate : null;
        $subtotal = $rate !== null ? $rate * $days : null;
        $deposit = $subtotal !== null ? round($subtotal * 0.30, 2) : null;

        return [
            'event_type' => $data['event_type'],
            'days' => $days,
            'rate' => $rate,
            'subtotal' => $subtotal,
            'deposit' => $deposit,
            'total' => $subtotal,
            'currency' => 'KES',
            'on_request' => $rate === null,
        ];
    }
}
