<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * TravelInventorySeeder — the certified-provider travel inventory.
 *
 * Airlines, airports, daily flights (with 60 days of seat inventory),
 * hotels + rooms, airport transfer (cab) providers, and the provider
 * user accounts that manage them in the provider portal.
 */
class TravelInventorySeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAirports();
        $this->seedAirlinesAndFlights();
        $this->seedHotels();
        $this->seedTransfers();
        $this->seedProviderUsers();
    }

    private function seedAirports(): void
    {
        $airports = [
            ['name' => 'Jomo Kenyatta International', 'iata_code' => 'NBO', 'icao' => 'HKJK', 'city' => 'Nairobi', 'county' => 'nairobi-city', 'intl' => 1, 'lat' => -1.3192, 'lng' => 36.9278, 'alt' => 5330],
            ['name' => 'Wilson Airport', 'iata_code' => 'WIL', 'icao' => 'HKNW', 'city' => 'Nairobi', 'county' => 'nairobi-city', 'intl' => 0, 'lat' => -1.3217, 'lng' => 36.8148, 'alt' => 5546],
            ['name' => 'Moi International', 'iata_code' => 'MBA', 'icao' => 'HKMO', 'city' => 'Mombasa', 'county' => 'mombasa', 'intl' => 1, 'lat' => -4.0347, 'lng' => 39.5942, 'alt' => 200],
            ['name' => 'Ukunda Airstrip', 'iata_code' => 'UKA', 'icao' => 'HKUK', 'city' => 'Diani', 'county' => 'kwale', 'intl' => 0, 'lat' => -4.2933, 'lng' => 39.5711, 'alt' => 98],
            ['name' => 'Malindi Airport', 'iata_code' => 'MYD', 'icao' => 'HKML', 'city' => 'Malindi', 'county' => 'kilifi', 'intl' => 0, 'lat' => -3.2293, 'lng' => 40.1017, 'alt' => 80],
            ['name' => 'Kisumu International', 'iata_code' => 'KIS', 'icao' => 'HKKI', 'city' => 'Kisumu', 'county' => 'kisumu', 'intl' => 1, 'lat' => -0.0861, 'lng' => 34.7289, 'alt' => 3796],
            ['name' => 'Eldoret International', 'iata_code' => 'EDL', 'icao' => 'HKEL', 'city' => 'Eldoret', 'county' => 'uasin-gishu', 'intl' => 1, 'lat' => 0.4044, 'lng' => 35.2389, 'alt' => 6941],
        ];
        foreach ($airports as $a) {
            $countyId = DB::table('counties')->where('slug', $a['county'])->value('id');
            DB::table('airports')->updateOrInsert(
                ['iata_code' => $a['iata_code']],
                ['name' => $a['name'], 'icao_code' => $a['icao'], 'city' => $a['city'],
                 'county_id' => $countyId, 'country' => 'KE', 'is_international' => $a['intl'],
                 'latitude' => $a['lat'], 'longitude' => $a['lng'], 'altitude_ft' => $a['alt'],
                 'is_active' => 1, 'timezone' => 'Africa/Nairobi', 'created_at' => now(), 'updated_at' => now()]
            );
        }
        $this->command->info('Airports: ' . DB::table('airports')->count());
    }

    private function seedAirlinesAndFlights(): void
    {
        $airlines = [
            ['name' => 'Kenya Airways', 'iata_code' => 'KQ'],
            ['name' => 'Jambojet', 'iata_code' => 'JM'],
            ['name' => 'Safarilink', 'iata_code' => 'SL'],
        ];
        foreach ($airlines as $a) {
            DB::table('airlines')->updateOrInsert(
                ['iata_code' => $a['iata_code']],
                ['name' => $a['name'], 'icao_code' => $a['iata_code'], 'country' => 'KE', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()]
            );
        }

        $routes = [
            // [origin, dest, airline, dep, arr, dur, price, aircraft]
            ['NBO', 'MBA', 'KQ', '07:30', '08:40', 70, 12500, 'B737'],
            ['NBO', 'MBA', 'JM', '10:15', '11:20', 65, 8400, 'Dash 8'],
            ['NBO', 'MBA', 'JM', '16:45', '17:50', 65, 9200, 'Dash 8'],
            ['MBA', 'NBO', 'KQ', '19:00', '20:10', 70, 11800, 'B737'],
            ['NBO', 'UKA', 'SL', '08:00', '09:15', 75, 14200, 'Cessna 208'],
            ['NBO', 'MYD', 'SL', '09:30', '10:40', 70, 13600, 'Cessna 208'],
            ['NBO', 'KIS', 'JM', '08:45', '09:45', 60, 7800, 'Dash 8'],
            ['NBO', 'EDL', 'JM', '11:00', '11:55', 55, 7200, 'Dash 8'],
        ];

        foreach ($routes as $ri => [$o, $d, $al, $dep, $arr, $dur, $price, $craft]) {
            $airlineId = DB::table('airlines')->where('iata_code', $al)->value('id');
            $originId = DB::table('airports')->where('iata_code', $o)->value('id');
            $destId = DB::table('airports')->where('iata_code', $d)->value('id');
            if (!$airlineId || !$originId || !$destId) continue;

            DB::table('flights')->updateOrInsert(
                ['flight_number' => $al . str_replace(':', '', $dep) . chr(65 + $ri)],
                [
                    'airline_id' => $airlineId, 'origin_airport_id' => $originId, 'destination_airport_id' => $destId,
                    'departure_time' => $dep, 'arrival_time' => $arr, 'duration_minutes' => $dur,
                    'days_of_week' => '1,2,3,4,5,6,7', 'aircraft_type' => $craft,
                    'base_price' => $price, 'currency' => 'KES', 'status' => 'active',
                    'created_at' => now(), 'updated_at' => now(),
                ]
            );
        }

        // 60 days of seat inventory per flight
        $flights = DB::table('flights')->get();
        foreach ($flights as $f) {
            for ($i = 0; $i < 60; $i++) {
                $date = now()->addDays($i)->toDateString();
                $weekend = in_array(now()->addDays($i)->dayOfWeek, [5, 6]);
                DB::table('flight_inventory')->updateOrInsert(
                    ['flight_id' => $f->id, 'date' => $date, 'fare_class' => 'economy'],
                    [
                        'total_seats' => 40, 'available_seats' => rand(6, 40),
                        'price' => round($f->base_price * ($weekend ? 1.25 : 1.0) * (1 + $i * 0.002)),
                        'currency' => 'KES', 'is_active' => 1,
                        'created_at' => now(), 'updated_at' => now(),
                    ]
                );
            }
        }
        $this->command->info('Flights: ' . DB::table('flights')->count() . ' | inventory: ' . DB::table('flight_inventory')->count());
    }

    private function seedHotels(): void
    {
        $hotels = [
            ['county' => 'mombasa', 'name' => 'Villarosa Kempinski Mombasa', 'stars' => 5, 'rooms' => [
                ['name' => 'Ocean View Deluxe', 'type' => 'deluxe', 'price' => 22000, 'guests' => 2],
                ['name' => 'Presidential Suite', 'type' => 'suite', 'price' => 65000, 'guests' => 4],
            ]],
            ['county' => 'mombasa', 'name' => 'Nyali Beach Resort', 'stars' => 4, 'rooms' => [
                ['name' => 'Garden Room', 'type' => 'standard', 'price' => 9500, 'guests' => 2],
                ['name' => 'Sea Facing Room', 'type' => 'deluxe', 'price' => 14500, 'guests' => 3],
            ]],
            ['county' => 'kwale', 'name' => 'Diani Reef Beach Resort', 'stars' => 5, 'rooms' => [
                ['name' => 'Deluxe Ocean Front', 'type' => 'deluxe', 'price' => 19500, 'guests' => 2],
            ]],
            ['county' => 'kilifi', 'name' => 'Malindi Palm Hotel', 'stars' => 3, 'rooms' => [
                ['name' => 'Standard Double', 'type' => 'standard', 'price' => 6800, 'guests' => 2],
            ]],
            ['county' => 'nairobi-city', 'name' => 'KICC City Hotel', 'stars' => 4, 'rooms' => [
                ['name' => 'Executive Room', 'type' => 'deluxe', 'price' => 11800, 'guests' => 2],
                ['name' => 'Family Suite', 'type' => 'suite', 'price' => 24000, 'guests' => 5],
            ]],
        ];

        foreach ($hotels as $h) {
            $countyId = DB::table('counties')->where('slug', $h['county'])->value('id');
            if (!$countyId) continue;
            $slug = \Illuminate\Support\Str::slug($h['name']);
            DB::table('hotels')->updateOrInsert(
                ['slug' => $slug],
                [
                    'county_id' => $countyId, 'name' => $h['name'], 'star_rating' => $h['stars'],
                    'description' => "{$h['name']} — certified KICC platform partner hotel.",
                    'address' => $h['county'], 'latitude' => -4.0, 'longitude' => 39.6,
                    'check_in_time' => '14:00', 'check_out_time' => '11:00',
                    'amenities' => json_encode(['WiFi', 'Pool', 'Restaurant', 'Airport pickup']),
                    'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
                ]
            );
            $hotelId = DB::table('hotels')->where('slug', $slug)->value('id');
            foreach ($h['rooms'] as $r) {
                DB::table('hotel_rooms')->updateOrInsert(
                    ['hotel_id' => $hotelId, 'name' => $r['name']],
                    ['room_type' => $r['type'], 'max_guests' => $r['guests'], 'total_rooms' => 10,
                     'price_per_night' => $r['price'], 'currency' => 'KES', 'is_active' => 1,
                     'created_at' => now(), 'updated_at' => now()]
                );
            }
        }
        $this->command->info('Hotels: ' . DB::table('hotels')->count() . ' | rooms: ' . DB::table('hotel_rooms')->count());
    }

    private function seedTransfers(): void
    {
        $transfers = [
            ['MBA', 'Mombasa Cab Co.', 'economy', 3, 1800],
            ['MBA', 'Mombasa Cab Co.', 'comfort', 3, 3200],
            ['MBA', 'Coast Shuttles', 'van', 7, 5500],
            ['MBA', 'Kenya Helicopter Rides', 'helicopter', 4, 48000],
            ['UKA', 'Diani Taxis', 'economy', 3, 1500],
            ['UKA', 'Diani Taxis', 'van', 7, 4200],
            ['MYD', 'Malindi Cabs', 'economy', 3, 1600],
            ['NBO', 'Nairobi Express Cabs', 'economy', 3, 2500],
            ['NBO', 'Nairobi Express Cabs', 'comfort', 3, 4500],
            ['NBO', 'Kenya Helicopter Rides', 'helicopter', 4, 55000],
            ['KIS', 'Kisumu Cabs', 'economy', 3, 1400],
        ];
        foreach ($transfers as [$iata, $provider, $type, $cap, $price]) {
            $airportId = DB::table('airports')->where('iata_code', $iata)->value('id');
            if (!$airportId) continue;
            DB::table('airport_transfers')->updateOrInsert(
                ['airport_id' => $airportId, 'provider_name' => $provider, 'vehicle_type' => $type],
                ['capacity' => $cap, 'price' => $price, 'currency' => 'KES',
                 'description' => "{$provider} — {$type} transfer (seats {$cap})",
                 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()]
            );
        }
        $this->command->info('Transfers: ' . DB::table('airport_transfers')->count());
    }

    private function seedProviderUsers(): void
    {
        $providers = [
            ['name' => 'Jambojet Ops', 'email' => 'ops@jambojet.kicc.ke', 'type' => 'airline', 'link' => ['airline_code' => 'JM']],
            ['name' => 'Villarosa Kempinski', 'email' => 'manager@villarosa.kicc.ke', 'type' => 'hotel', 'link' => ['hotel_slug' => 'villarosa-kempinski-mombasa']],
            ['name' => 'Mombasa Cab Co.', 'email' => 'dispatch@mombasacab.kicc.ke', 'type' => 'transfer', 'link' => ['provider_name' => 'Mombasa Cab Co.']],
            ['name' => 'Kenya Helicopter Rides', 'email' => 'book@helicopter.kicc.ke', 'type' => 'transfer', 'link' => ['provider_name' => 'Kenya Helicopter Rides']],
        ];
        foreach ($providers as $p) {
            $meta = ['provider_type' => $p['type'], 'company_name' => $p['name'], 'approved' => true] + $p['link'];
            $user = User::updateOrCreate(
                ['email' => $p['email']],
                [
                    'name' => $p['name'], 'password' => bcrypt('Provider@2026'),
                    'account_type' => 'provider', 'status' => 'active',
                    'email_verified_at' => now(),
                    'metadata' => $meta,
                ]
            );
            $user->syncRoles([]);
        }
        $this->command->info('Provider users: ' . User::where('account_type', 'provider')->count());
    }
}
