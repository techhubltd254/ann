<?php

namespace Database\Seeders;

use App\Models\Venue;
use Illuminate\Database\Seeder;

class KiccServicesSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = [
            ['name'=>'Tsavo Hall','slug'=>'tsavo-hall','venue_type'=>'ballroom','capacity'=>2000,'description'=>'Grand ballroom — KICC\'s flagship space. Hosts state banquets, summits, and plenary sessions. Column-free, 2000 pax theatre style.','amenities'=>json_encode(['stage','sound system','projector','air conditioning','backstage','dressing rooms','VIP lounge']),'city'=>'Nairobi','is_active'=>true],
            ['name'=>'Amphitheatre','slug'=>'amphitheatre','venue_type'=>'theatre','capacity'=>600,'description'=>'Indoor amphitheatre with tiered seating. Perfect for presentations, product launches, and performances.','amenities'=>json_encode(['tiered seating','stage','lighting rig','acoustic panels','projector']),'city'=>'Nairobi','is_active'=>true],
            ['name'=>'Aberdares','slug'=>'aberdares','venue_type'=>'meeting','capacity'=>80,'description'=>'Executive boardroom with panoramic Nairobi skyline views. U-shape seating for 80.','amenities'=>json_encode(['boardroom table','video conferencing','whiteboard','refreshment bar','city view']),'city'=>'Nairobi','is_active'=>true],
            ['name'=>'Lenana Hills','slug'=>'lenana-hills','venue_type'=>'meeting','capacity'=>60,'description'=>'Premium meeting room on the helipad floor. Natural light, adjoining terrace.','amenities'=>json_encode(['terrace','natural light','video conference','dedicated WiFi','refreshments']),'city'=>'Nairobi','is_active'=>true],
            ['name'=>'Shimba Hills Room','slug'=>'shimba-hills-room','venue_type'=>'meeting','capacity'=>40,'description'=>'Intimate boardroom with Mount Kenya views. Ideal for VIP briefings and closed sessions.','amenities'=>json_encode(['executive seating','mountain view','silent AC','secure entry']),'city'=>'Nairobi','is_active'=>true],
            ['name'=>'Courtyard','slug'=>'courtyard','venue_type'=>'outdoor','capacity'=>400,'description'=>'Open-air courtyard surrounded by tropical gardens. Cocktail receptions, evening galas.','amenities'=>json_encode(['garden setting','evening lighting','dance floor','catering kitchen','backup tent']),'city'=>'Nairobi','is_active'=>true],
            ['name'=>'Lawn','slug'=>'lawn','venue_type'=>'outdoor','capacity'=>1500,'description'=>'Expansive manicured lawn. Kenya\'s largest outdoor event space. Tents, exhibitions, open-air concerts.','amenities'=>json_encode(['manicured lawn','tent-ready','power distribution','water points','parking']),'city'=>'Nairobi','is_active'=>true],
            ['name'=>'Upper COMESA','slug'=>'upper-comesa','venue_type'=>'meeting','capacity'=>120,'description'=>'Upper-level COMESA conference room. Soundproofed, divisible into two breakout rooms.','amenities'=>json_encode(['divisible walls','soundproofing','breakout rooms','projector','PA system']),'city'=>'Nairobi','is_active'=>true],
            ['name'=>'Lower COMESA','slug'=>'lower-comesa','venue_type'=>'meeting','capacity'=>100,'description'=>'Ground-level COMESA hall. Direct outdoor access, ideal for exhibitions with meeting component.','amenities'=>json_encode(['outdoor access','exhibition floor','wifi','catering','ADA accessible']),'city'=>'Nairobi','is_active'=>true],
        ];

        $count = 0;
        foreach ($rooms as $r) {
            Venue::updateOrCreate(['slug' => $r['slug']], $r);
            $count++;
        }

        // services as a pseudo-venue type for MICE
        Venue::updateOrCreate(['slug' => 'mice-services'], [
            'name' => 'MICE Services',
            'venue_type' => 'service',
            'description' => 'Meetings, Incentives, Conferences, Exhibitions — KICC\'s complete event ecosystem. From audio-visual and catering to event planning and technical support, every service is in-house.',
            'amenities' => json_encode(['Audio-visual Equipment','Catering Services','Event Planning','Technical Support','Wi-Fi & Internet','Security / Fire','Parking','Accessibility','Tourist Information']),
            'city' => 'Nairobi',
            'is_active' => true,
        ]);

        $this->command->info("Seeded {$count} KICC rooms + MICE services");
    }
}
