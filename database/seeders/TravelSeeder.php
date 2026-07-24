<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TravelSeeder extends Seeder
{
    public function run(): void
    {
        // Airlines
        DB::table('airlines')->insert([
            ['code' => 'KQ', 'name' => 'Kenya Airways', 'country' => 'Kenya', 'iata_code' => 'KQ', 'is_active' => true],
            ['code' => 'JM', 'name' => 'Jambojet', 'country' => 'Kenya', 'iata_code' => 'JM', 'is_active' => true],
            ['code' => 'EK', 'name' => 'Emirates', 'country' => 'UAE', 'iata_code' => 'EK', 'is_active' => true],
        ]);

        // Airports
        DB::table('airports')->insert([
            ['code' => 'NBO', 'name' => 'Jomo Kenyatta International Airport', 'city' => 'Nairobi', 'country' => 'Kenya', 'iata_code' => 'NBO', 'type' => 'international'],
            ['code' => 'MBA', 'name' => 'Moi International Airport', 'city' => 'Mombasa', 'country' => 'Kenya', 'iata_code' => 'MBA', 'type' => 'international'],
            ['code' => 'KIS', 'name' => 'Kisumu International Airport', 'city' => 'Kisumu', 'country' => 'Kenya', 'iata_code' => 'KIS', 'type' => 'domestic'],
            ['code' => 'EDL', 'name' => 'Eldoret International Airport', 'city' => 'Eldoret', 'country' => 'Kenya', 'iata_code' => 'EDL', 'type' => 'domestic'],
        ]);

        // Hotels
        DB::table('hotels')->insert([
            ['name' => 'Hilton Nairobi', 'city' => 'Nairobi', 'county_id' => 1, 'star_rating' => 5, 'latitude' => -1.286, 'longitude' => 36.817, 'is_active' => true],
            ['name' => 'Sarova Stanley', 'city' => 'Nairobi', 'county_id' => 1, 'star_rating' => 5, 'latitude' => -1.285, 'longitude' => 36.821, 'is_active' => true],
            ['name' => 'Voyager Beach Resort', 'city' => 'Mombasa', 'county_id' => 1, 'star_rating' => 4, 'latitude' => -4.078, 'longitude' => 39.664, 'is_active' => true],
            ['name' => 'PrideInn Paradise', 'city' => 'Mombasa', 'county_id' => 1, 'star_rating' => 4, 'latitude' => -3.971, 'longitude' => 39.739, 'is_active' => true],
            ['name' => 'Lake Nakuru Lodge', 'city' => 'Nakuru', 'county_id' => 18, 'star_rating' => 4, 'latitude' => -0.365, 'longitude' => 36.089, 'is_active' => true],
        ]);

        // Restaurants
        DB::table('restaurants')->insert([
            ['name' => 'Talisman Restaurant', 'city' => 'Nairobi', 'county_id' => 1, 'cuisine' => 'African', 'is_active' => true],
            ['name' => 'Carnivore', 'city' => 'Nairobi', 'county_id' => 1, 'cuisine' => 'Nyama Choma', 'is_active' => true],
            ['name' => 'Tamarind Dhow', 'city' => 'Mombasa', 'county_id' => 1, 'cuisine' => 'Swahili', 'is_active' => true],
        ]);

        // Attractions
        DB::table('attractions')->insert([
            ['name' => 'Amboseli National Park', 'city' => 'Kajiado', 'county_id' => 10, 'type' => 'National Park', 'is_active' => true],
            ['name' => 'Maasai Mara National Reserve', 'city' => 'Narok', 'county_id' => 16, 'type' => 'National Reserve', 'is_active' => true],
            ['name' => 'Lake Nakuru National Park', 'city' => 'Nakuru', 'county_id' => 18, 'type' => 'National Park', 'is_active' => true],
            ['name' => 'Fort Jesus', 'city' => 'Mombasa', 'county_id' => 1, 'type' => 'UNESCO', 'is_active' => true],
            ['name' => 'Mount Kenya', 'city' => 'Nyeri', 'county_id' => 26, 'type' => 'Mountain', 'is_active' => true],
            ['name' => 'Diani Beach', 'city' => 'Kwale', 'county_id' => 4, 'type' => 'Beach', 'is_active' => true],
        ]);

        $this->command->info('Seeded travel data: airlines, airports, hotels, restaurants, attractions');
    }
}
