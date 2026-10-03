<?php
class VenuesSeeder
{
    public function run(): void
    {
        echo "Seeding 10 KICC venues...\n";
        
        $venues = [
            ['name' => 'Tsavo Hall', 'slug' => 'tsavo-hall', 'venue_type' => 'Plenary Hall', 'capacity' => 2400,
             'description' => 'Our flagship hall. Hosts up to 2,400 delegates theatre-style. Used for UN assemblies, AU summits, and global conferences.',
             'amenities' => json_encode(['Stage','LED Walls','Interpretation Booths','VIP Boxes','Backstage']), 'is_active' => true],
            ['name' => 'Amphitheatre', 'slug' => 'amphitheatre', 'venue_type' => 'Theatre', 'capacity' => 700,
             'description' => 'Tiered seating for 700. Perfect for keynote speeches, product launches, and performances.',
             'amenities' => json_encode(['Tiered Seating','Stage','Sound System','Backstage']), 'is_active' => true],
            ['name' => 'Aberdares', 'slug' => 'aberdares', 'venue_type' => 'Meeting Room', 'capacity' => 250,
             'description' => 'Executive boardroom for 250. Natural light with city views and built-in AV.',
             'amenities' => json_encode(['Boardroom','City View','Built-in AV','Natural Light']), 'is_active' => true],
            ['name' => 'Lenana Hills', 'slug' => 'lenana-hills', 'venue_type' => 'Boardroom', 'capacity' => 80,
             'description' => 'Intimate boardroom for up to 80 delegates. Ideal for C-suite meetings and private dinners.',
             'amenities' => json_encode(['Boardroom','City View','Private Dining']), 'is_active' => true],
            ['name' => 'Shimba Hills Room', 'slug' => 'shimba-hills', 'venue_type' => 'Meeting Room', 'capacity' => 120,
             'description' => 'Flexible meeting space for 120. Breakout sessions, workshops, and training.',
             'amenities' => json_encode(['Flexible Layout','Breakout Space','AV Ready']), 'is_active' => true],
            ['name' => 'Courtyard', 'slug' => 'courtyard', 'venue_type' => 'Outdoor', 'capacity' => 500,
             'description' => 'Open-air courtyard for 500. Cocktail receptions, garden parties, and networking.',
             'amenities' => json_encode(['Open Air','Landscaped','Covered Area Available','Lighting']), 'is_active' => true],
            ['name' => 'Lawn', 'slug' => 'lawn', 'venue_type' => 'Outdoor', 'capacity' => 1000,
             'description' => 'Expansive lawn for 1,000 delegates. Tented events, galas, and large-scale exhibitions.',
             'amenities' => json_encode(['Tented Options','Stage','Power Supply']), 'is_active' => true],
            ['name' => 'Upper COMESA', 'slug' => 'upper-comesa', 'venue_type' => 'Meeting Room', 'capacity' => 300,
             'description' => 'Upper-level conference room with natural light and Nairobi skyline views.',
             'amenities' => json_encode(['Conference','City View','Natural Light']), 'is_active' => true],
            ['name' => 'Lower COMESA', 'slug' => 'lower-comesa', 'venue_type' => 'Meeting Room', 'capacity' => 200,
             'description' => 'Lower-level conference room for breakout sessions, parallel tracks, and media centre.',
             'amenities' => json_encode(['Breakout Space','Media Ready']), 'is_active' => true],
            ['name' => 'Helipad', 'slug' => 'helipad', 'venue_type' => 'VIP/Events', 'capacity' => null,
             'description' => 'Rooftop helipad with panoramic Nairobi views. Exclusive cocktail events and VIP arrivals.',
             'amenities' => json_encode(['Panoramic View','VIP','Cocktail Events']), 'is_active' => true],
        ];

        foreach ($venues as $data) {
            \App\Models\Venue::create($data);
            echo "  + $data[name]\n";
        }
        echo "Done. " . count($venues) . " venues seeded.\n";
    }
}
