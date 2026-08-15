<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\Travel\Attraction;
use App\Models\Travel\Hotel;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * The complete travel journey:
 *   destination (weather-aware) → flights by date → package builder
 *   (hotel + cab allocation) → pay → receipt.
 */
class TravelController extends Controller
{
    public function index(Request $request)
    {
        $attractions = Attraction::with('county')->where('is_active', true)->get();
        $hotels = Hotel::with('county')->where('is_active', true)->get();
        $counties = County::orderBy('name')->get(['id', 'name', 'slug']);

        // Destinations = airports served, with cheapest upcoming flight
        $destinations = DB::table('airports')
            ->where('airports.is_active', 1)->where('iata_code', '!=', 'NBO')->where('iata_code', '!=', 'WIL')
            ->get()->map(function ($ap) {
                $ap->from_price = DB::table('flight_inventory')
                    ->join('flights', 'flight_inventory.flight_id', '=', 'flights.id')
                    ->where('flights.destination_airport_id', $ap->id)
                    ->where('flight_inventory.date', '>=', now()->toDateString())
                    ->min('flight_inventory.price');
                $ap->county_slug = DB::table('counties')->where('id', $ap->county_id)->value('slug');
                return $ap;
            })->filter(fn ($d) => $d->from_price);

        // Weather-aware recommendation ("someone in cold UK must see a Kenya summer")
        $weather = $this->originWeather($request->query('from', 'London'));

        return view('travel.index', compact('attractions', 'hotels', 'counties', 'destinations', 'weather'));
    }

    /** Available flights + package builder for a destination & date. */
    public function flights(Request $request)
    {
        $data = $request->validate([
            'to' => 'required|string|size:3',
            'date' => 'required|date|after_or_equal:today',
            'from' => 'nullable|string|size:3',
        ]);

        $origin = DB::table('airports')->where('iata_code', $data['from'] ?? 'NBO')->first() ?? DB::table('airports')->where('iata_code', 'NBO')->first();
        $destination = DB::table('airports')->where('iata_code', $data['to'])->firstOrFail();

        $flights = DB::table('flight_inventory')
            ->join('flights', 'flight_inventory.flight_id', '=', 'flights.id')
            ->join('airlines', 'flights.airline_id', '=', 'airlines.id')
            ->where('flights.origin_airport_id', $origin->id)
            ->where('flights.destination_airport_id', $destination->id)
            ->where('flight_inventory.date', $data['date'])
            ->where('flight_inventory.is_active', 1)
            ->where('flight_inventory.available_seats', '>', 0)
            ->orderBy('flights.departure_time')
            ->get([
                'flight_inventory.id as inventory_id', 'flight_inventory.price', 'flight_inventory.available_seats',
                'flights.flight_number', 'flights.departure_time', 'flights.arrival_time', 'flights.duration_minutes', 'flights.aircraft_type',
                'airlines.name as airline_name', 'airlines.iata_code',
            ]);

        // Package components at destination
        $countyId = $destination->county_id;
        $hotels = DB::table('hotels')->where('county_id', $countyId)->where('is_active', 1)->get()
            ->map(fn ($h) => tap($h, fn ($x) => $x->rooms = DB::table('hotel_rooms')->where('hotel_id', $h->id)->where('is_active', 1)->orderBy('price_per_night')->get()));
        $transfers = DB::table('airport_transfers')->where('airport_id', $destination->id)->where('is_active', 1)->orderBy('price')->get();

        return view('travel.flights', [
            'origin' => $origin, 'destination' => $destination, 'date' => $data['date'],
            'flights' => $flights, 'hotels' => $hotels, 'transfers' => $transfers,
        ]);
    }

    /** Create flight + hotel + transfer bookings, charge, show receipt. */
    public function book(Request $request, PaymentService $payments)
    {
        $data = $request->validate([
            'inventory_id' => 'required|integer',
            'passengers' => 'required|integer|min:1|max:9',
            'room_id' => 'nullable|integer',
            'nights' => 'nullable|integer|min:1|max:30',
            'transfer_id' => 'nullable|integer',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:30',
        ]);

        $inventory = DB::table('flight_inventory')->where('id', $data['inventory_id'])->where('is_active', 1)->first();
        abort_unless($inventory && $inventory->available_seats >= $data['passengers'], 422, 'Flight no longer available for that many passengers.');
        $flight = DB::table('flights')->find($inventory->flight_id);

        $guestId = $request->user()?->id ?? \App\Models\User::where('email', 'guest@kicc.go.ke')->value('id');
        $groupRef = 'TRV-' . strtoupper(Str::random(8));
        $bookings = [];
        $total = 0;
        $flightBooking = null;

        DB::transaction(function () use ($data, $inventory, $flight, $guestId, $groupRef, &$bookings, &$total, &$flightBooking) {
            // 1. Flight booking
                $flightTotal = $inventory->price * $data['passengers'];
            $flightBooking = \App\Models\Travel\FlightBooking::create([
                'booking_reference' => $groupRef . '-FL', 'user_id' => $guestId, 'flight_id' => $flight->id,
                'flight_inventory_id' => $inventory->id, 'fare_class' => 'economy',
                'passenger_count' => $data['passengers'], 'subtotal' => $flightTotal, 'tax' => 0,
                'total' => $flightTotal, 'currency' => 'KES', 'status' => 'confirmed',
                'pnr_code' => strtoupper(Str::random(6)), 'booked_at' => now(),
            ]);
            try {
                DB::table('flight_inventory')->where('id', $inventory->id)->decrement('available_seats', $data['passengers']);
            } catch (\Throwable $e) {
                DB::table('flight_inventory')->where('id', $inventory->id)->increment('available_seats', $data['passengers']);
                throw $e;
            }
            $bookings[] = ['type' => 'Flight', 'ref' => $flightBooking->booking_reference, 'total' => $flightTotal];
            $total += $flightTotal;

            // 2. Hotel booking (optional)
            if (!empty($data['room_id'])) {
                $room = DB::table('hotel_rooms')->find($data['room_id']);
                $nights = $data['nights'] ?? 2;
                if ($room) {
                    $hotelTotal = $room->price_per_night * $nights;
                    $hbRef = $groupRef . '-HT';
                    DB::table('hotel_bookings')->insert([
                        'booking_reference' => $hbRef, 'user_id' => $guestId, 'hotel_id' => $room->hotel_id,
                        'check_in' => $inventory->date, 'check_out' => date('Y-m-d', strtotime($inventory->date . " +{$nights} days")),
                        'guest_count' => $data['passengers'], 'subtotal' => $hotelTotal, 'tax' => 0,
                        'total' => $hotelTotal, 'currency' => 'KES', 'status' => 'confirmed',
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                    $bookings[] = ['type' => 'Hotel', 'ref' => $hbRef, 'total' => $hotelTotal];
                    $total += $hotelTotal;
                }
            }

            // 3. Transfer / cab allocation (optional — allocated by chosen vehicle type)
            if (!empty($data['transfer_id'])) {
                $transfer = DB::table('airport_transfers')->find($data['transfer_id']);
                if ($transfer) {
                    $tbRef = $groupRef . '-TR';
                    DB::table('transfer_bookings')->insert([
                        'booking_reference' => $tbRef, 'user_id' => $guestId, 'transfer_id' => $transfer->id,
                        'flight_booking_id' => $flightBooking?->id,
                        'pickup_location' => 'Airport', 'dropoff_location' => 'Hotel',
                        'pickup_datetime' => $inventory->date . ' ' . $flight->arrival_time,
                        'flight_number' => $flight->flight_number,
                        'passenger_count' => $data['passengers'], 'total' => $transfer->price,
                        'currency' => 'KES', 'status' => 'allocated',
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                    $bookings[] = ['type' => 'Transfer (' . $transfer->vehicle_type . ')', 'ref' => $tbRef, 'total' => $transfer->price];
                    $total += $transfer->price;
                }
            }
        });

        // Unified payment — intent references the flight booking (anchor of the package)
        try {
            $payments->charge($flightBooking, $total, [
                'description' => "Travel package {$groupRef}",
                'phone' => $data['phone'],
            ]);
        } catch (\Throwable $e) {
            if ($flightBooking) {
                DB::table('flight_inventory')->where('id', $inventory->id)->increment('available_seats', $data['passengers']);
            }
            throw $e;
        }

        \App\Services\N8nService::fire('booking_created', [
            'type' => 'travel_package', 'reference' => $groupRef, 'total' => $total,
            'components' => array_column($bookings, 'type'),
        ]);

        return redirect()->route('travel.receipt', $groupRef);
    }

    /** Receipt — every component with references and totals. */
    public function receipt(string $groupRef)
    {
        $booking = \App\Models\Travel\FlightBooking::where('booking_reference', $groupRef . '-FL')->firstOrFail();
        abort_if($booking->user_id !== auth()->id() && !auth()->user()?->is_admin, 403);

        $flight = DB::table('flight_bookings')->where('booking_reference', $groupRef . '-FL')->first();
        abort_unless($flight, 404);
        $hotel = DB::table('hotel_bookings')->where('booking_reference', $groupRef . '-HT')->first();
        $transfer = DB::table('transfer_bookings')->where('booking_reference', $groupRef . '-TR')->first();

        $flightDetail = DB::table('flights')->find($flight->flight_id);
        $hotelDetail = $hotel ? DB::table('hotels')->find($hotel->hotel_id) : null;
        $transferDetail = $transfer ? DB::table('airport_transfers')->find($transfer->transfer_id) : null;

        $total = $flight->total + ($hotel->total ?? 0) + ($transfer->total ?? 0);

        return view('travel.receipt', compact('groupRef', 'flight', 'hotel', 'transfer', 'flightDetail', 'hotelDetail', 'transferDetail', 'total'));
    }

    /** Current weather for an origin city (cached, graceful fallback). */
    private function originWeather(string $city): array
    {
        return Cache::remember("weather:{$city}", 3600, function () use ($city) {
            try {
                $r = Http::timeout(4)->get("https://wttr.in/" . urlencode($city) . "?format=j1");
                if ($r->ok()) {
                    $cur = $r->json('current_condition.0');
                    return ['city' => $city, 'temp' => (int) ($cur['temp_C'] ?? 0), 'desc' => $cur['weatherDesc'][0]['value'] ?? '', 'live' => true];
                }
            } catch (\Throwable) {}
            // Seasonal fallback (Dec-Feb UK/EU winter heuristic)
            $cold = in_array((int) now()->format('n'), [11, 12, 1, 2, 3]);
            return ['city' => $city, 'temp' => $cold ? 6 : 16, 'desc' => $cold ? 'Cold' : 'Mild', 'live' => false];
        });
    }
}
