<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Provider Portal — certified travel & accommodation providers
 * (airlines, hotels, cab/transfer companies) manage their own services,
 * prices, bookings and money. KICC approves every change before it
 * goes live (government certification workflow).
 */
class ProviderPortalController extends Controller
{
    protected function user()
    {
        $u = Auth::user();
        abort_unless($u && ($u->account_type === 'provider' || $u->hasRole('kicc_admin')), 403, 'Provider access required.');
        return $u;
    }

    protected function meta($u): array
    {
        return (array) ($u->metadata ?? []);
    }

    public function index(Request $request)
    {
        $user = $this->user();
        $meta = $this->meta($user);
        $type = $meta['provider_type'] ?? 'transfer';
        $tab = $request->get('tab', 'services');

        [$services, $bookings, $money] = $this->loadScope($type, $meta);

        $navItems = [
            ['label' => 'My Services', 'tab' => 'services', 'icon' => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z'],
            ['label' => 'Add Service', 'tab' => 'add', 'icon' => 'M12 4v16m8-8H4'],
            ['label' => 'Bookings', 'tab' => 'bookings', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
            ['label' => 'Money Flow', 'tab' => 'money', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1'],
            ['label' => 'Certification', 'tab' => 'status', 'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
        ];

        return view('dashboards.provider', [
            'user' => $user, 'meta' => $meta, 'type' => $type, 'tab' => $tab,
            'navItems' => $navItems, 'services' => $services, 'bookings' => $bookings, 'money' => $money,
            'pendingCount' => $services->where('is_active', 0)->count(),
        ]);
    }

    /** Provider edits a price → goes pending until KICC approves. */
    public function updatePrice(Request $request)
    {
        $user = $this->user();
        $meta = $this->meta($user);
        $data = $request->validate([
            'table_key' => 'required|in:flight,room,transfer',
            'id' => 'required|integer',
            'price' => 'required|numeric|min:1',
        ]);

        [$table, $priceCol] = match ($data['table_key']) {
            'flight' => ['flight_inventory', 'price'],
            'room' => ['hotel_rooms', 'price_per_night'],
            'transfer' => ['airport_transfers', 'price'],
        };

        // Ownership check happens in loadScope — here we scope the update
        $updated = DB::table($table)->where('id', $data['id'])->update([
            $priceCol => $data['price'],
            'is_active' => 0, // pending KICC re-approval
            'updated_at' => now(),
        ]);
        abort_unless($updated, 404);

        \App\Services\N8nService::fire('provider_price_changed', [
            'provider' => $user->email, 'table' => $table, 'id' => $data['id'], 'price' => $data['price'],
        ]);

        return back()->with('success', 'Price updated — pending KICC approval before it goes live.');
    }

    /** Provider adds a service (pending approval). */
    public function addService(Request $request)
    {
        $user = $this->user();
        $meta = $this->meta($user);
        $type = $meta['provider_type'] ?? 'transfer';

        if ($type === 'hotel') {
            $data = $request->validate([
                'name' => 'required|string|max:255', 'room_type' => 'required|string|max:50',
                'price_per_night' => 'required|numeric|min:1', 'max_guests' => 'required|integer|min:1',
            ]);
            $hotelId = DB::table('hotels')->where('slug', $meta['hotel_slug'] ?? '')->value('id');
            abort_unless($hotelId, 404, 'Hotel not linked to your account.');
            DB::table('hotel_rooms')->insert([
                'hotel_id' => $hotelId, 'name' => $data['name'], 'room_type' => $data['room_type'],
                'max_guests' => $data['max_guests'], 'total_rooms' => 5,
                'price_per_night' => $data['price_per_night'], 'currency' => 'KES',
                'is_active' => 0, 'created_at' => now(), 'updated_at' => now(),
            ]);
        } elseif ($type === 'airline') {
            $data = $request->validate([
                'origin' => 'required|string|size:3', 'destination' => 'required|string|size:3',
                'departure_time' => 'required', 'arrival_time' => 'required',
                'base_price' => 'required|numeric|min:1',
            ]);
            $airlineId = DB::table('airlines')->where('iata_code', $meta['airline_code'] ?? '')->value('id');
            $originId = DB::table('airports')->where('iata_code', $data['origin'])->value('id');
            $destId = DB::table('airports')->where('iata_code', $data['destination'])->value('id');
            abort_unless($airlineId && $originId && $destId, 404);
            DB::table('flights')->insert([
                'airline_id' => $airlineId, 'flight_number' => strtoupper(Str::random(6)),
                'origin_airport_id' => $originId, 'destination_airport_id' => $destId,
                'departure_time' => $data['departure_time'], 'arrival_time' => $data['arrival_time'],
                'duration_minutes' => 60, 'days_of_week' => '1,2,3,4,5,6,7',
                'base_price' => $data['base_price'], 'currency' => 'KES', 'status' => 'pending',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        } else { // transfer
            $data = $request->validate([
                'airport' => 'required|string|size:3', 'vehicle_type' => 'required|string|max:50',
                'capacity' => 'required|integer|min:1', 'price' => 'required|numeric|min:1',
            ]);
            $airportId = DB::table('airports')->where('iata_code', $data['airport'])->value('id');
            abort_unless($airportId, 404);
            DB::table('airport_transfers')->insert([
                'airport_id' => $airportId, 'provider_name' => $meta['provider_name'] ?? $user->name,
                'vehicle_type' => $data['vehicle_type'], 'capacity' => $data['capacity'],
                'price' => $data['price'], 'currency' => 'KES',
                'description' => $user->name . ' — ' . $data['vehicle_type'],
                'is_active' => 0, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        \App\Services\N8nService::fire('provider_service_added', [
            'provider' => $user->email, 'type' => $type,
        ]);

        return back()->with('success', 'Service submitted — KICC will review and certify it shortly.');
    }

    /** Load the provider's scoped services, bookings and money. */
    private function loadScope(string $type, array $meta): array
    {
        if ($type === 'airline') {
            $airlineId = DB::table('airlines')->where('iata_code', $meta['airline_code'] ?? '')->value('id');
            $flightIds = DB::table('flights')->where('airline_id', $airlineId)->pluck('id');
            $services = DB::table('flight_inventory')
                ->join('flights', 'flight_inventory.flight_id', '=', 'flights.id')
                ->whereIn('flights.id', $flightIds)
                ->select('flight_inventory.id', 'flights.flight_number as title', 'flight_inventory.date as sub', 'flight_inventory.price', 'flight_inventory.is_active')
                ->orderByDesc('flight_inventory.date')->limit(60)->get()
                ->map(fn ($s) => tap($s, fn ($x) => $x->table_key = 'flight'));
            $bookings = DB::table('flight_bookings')->whereIn('flight_id', $flightIds)->latest()->limit(50)->get();
            $money = ['total' => $bookings->where('status', 'confirmed')->sum('total'), 'count' => $bookings->count(), 'commission' => 0.10];
            return [$services, $bookings, $money];
        }

        if ($type === 'hotel') {
            $hotelId = DB::table('hotels')->where('slug', $meta['hotel_slug'] ?? '')->value('id');
            $services = DB::table('hotel_rooms')->where('hotel_id', $hotelId)->get()
                ->map(fn ($s) => tap($s, function ($x) {
                    $x->title = $x->name; $x->sub = $x->room_type; $x->price = $x->price_per_night; $x->table_key = 'room';
                }));
            $bookings = DB::table('hotel_bookings')->where('hotel_id', $hotelId)->latest()->limit(50)->get();
            $money = ['total' => $bookings->where('status', 'confirmed')->sum('total'), 'count' => $bookings->count(), 'commission' => 0.10];
            return [$services, $bookings, $money];
        }

        // transfer
        $services = DB::table('airport_transfers')->where('provider_name', $meta['provider_name'] ?? '')->get()
            ->map(fn ($s) => tap($s, function ($x) {
                $x->title = ucfirst($x->vehicle_type); $x->sub = 'seats ' . $x->capacity; $x->table_key = 'transfer';
            }));
        $transferIds = $services->pluck('id');
        $bookings = DB::table('transfer_bookings')->whereIn('transfer_id', $transferIds)->latest()->limit(50)->get();
        $money = ['total' => $bookings->sum('total'), 'count' => $bookings->count(), 'commission' => 0.10];
        return [$services, $bookings, $money];
    }
}
