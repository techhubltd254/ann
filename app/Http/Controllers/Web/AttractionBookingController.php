<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CountyTourismAttraction;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Attraction booking — every tourist attraction is bookable.
 *
 * Flow: view images & pricing → pick date/guests → optional add-ons
 * (transport, flight, helicopter, restaurant — suggested from the first
 * choice) → pay via unified payment intent → success with reference.
 */
class AttractionBookingController extends Controller
{
    /** Optional add-ons suggested with every attraction booking (KES). */
    public const ADDONS = [
        'transport'  => ['label' => 'Private return transfer', 'desc' => 'Pick-up & drop-off from your town', 'price' => 3500, 'per_guest' => false],
        'flight'     => ['label' => 'Domestic flight', 'desc' => 'Nairobi → nearest county airstrip', 'price' => 8500, 'per_guest' => true],
        'helicopter' => ['label' => 'Helicopter scenic transfer', 'desc' => 'Nairobi → coast with aerial views', 'price' => 45000, 'per_guest' => true],
        'restaurant' => ['label' => 'Lunch at partner restaurant', 'desc' => 'Reserved table near the attraction', 'price' => 2000, 'per_guest' => true],
    ];

    public function show(int $id)
    {
        $attraction = CountyTourismAttraction::with('county')->where('is_published', true)->findOrFail($id);

        // Recommendations driven by the first choice: same county, different attraction
        $recommended = CountyTourismAttraction::where('county_id', $attraction->county_id)
            ->where('is_published', true)
            ->where('id', '!=', $attraction->id)
            ->inRandomOrder()->take(3)->get();

        $entryFee = ($attraction->entry_fee && $attraction->entry_fee > 0) ? $attraction->entry_fee : 500;

        return view('attractions.show', [
            'attraction' => $attraction,
            'recommended' => $recommended,
            'entryFee' => $entryFee,
            'addons' => self::ADDONS,
        ]);
    }

    public function book(Request $request, int $id, PaymentService $payments)
    {
        $attraction = CountyTourismAttraction::where('is_published', true)->findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:30',
            'visit_date' => 'required|date|after_or_equal:today',
            'ticket_count' => 'required|integer|min:1|max:50',
            'addons' => 'nullable|array',
            'addons.*' => 'in:' . implode(',', array_keys(self::ADDONS)),
        ]);

        $entryFee = ($attraction->entry_fee && $attraction->entry_fee > 0) ? $attraction->entry_fee : 500;
        $guests = (int) $data['ticket_count'];

        $total = $entryFee * $guests;
        $chosenAddons = [];
        foreach (($data['addons'] ?? []) as $key) {
            $a = self::ADDONS[$key];
            $cost = $a['per_guest'] ? $a['price'] * $guests : $a['price'];
            $chosenAddons[] = ['key' => $key, 'label' => $a['label'], 'cost' => $cost];
            $total += $cost;
        }

        $bookingId = DB::table('attraction_bookings')->insertGetId([
            'booking_reference' => 'ATB-' . strtoupper(Str::random(8)),
            'user_id' => $request->user()?->id ?? \App\Models\User::where('email', 'guest@kicc.go.ke')->value('id'),
            'attraction_id' => $attraction->id,
            'visit_date' => $data['visit_date'],
            'ticket_count' => $guests,
            'total' => $total,
            'currency' => 'KES',
            'status' => 'pending_payment',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $ref = DB::table('attraction_bookings')->where('id', $bookingId)->value('booking_reference');

        // Charge via unified payment intent (M-Pesa when configured)
        $intent = $payments->charge(
            $attraction->setAttribute('booking_ref', $ref),
            $total,
            ['description' => "Attraction booking {$ref}: {$attraction->name}", 'phone' => $data['phone']]
        );

        \App\Services\N8nService::fire('booking_created', [
            'type' => 'attraction', 'reference' => $ref, 'attraction' => $attraction->name,
            'total' => $total, 'addons' => $chosenAddons,
        ]);

        return view('attractions.success', [
            'attraction' => $attraction,
            'reference' => $ref,
            'guests' => $guests,
            'visitDate' => $data['visit_date'],
            'addons' => $chosenAddons,
            'total' => $total,
            'entryFee' => $entryFee,
        ]);
    }
}
