<?php

namespace Database\Seeders;

use App\Models\County;
use App\Models\CountyInstitution;
use App\Models\User;
use App\Services\InstitutionSyncService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Murang'a County — Cover-All-Sectors Institutions.
 *
 * These institutions don't run their own consumer websites, but are
 * described on county government pages, directories and guide sites.
 * Copy is intentionally concise — it sells the visitor EXPERIENCE
 * rather than dumping raw boilerplate. Sector mappings use the exact
 * sector slugs so the automatic sync maps with full accuracy.
 *
 * Idempotent — upserts by slug, never duplicates.
 */
class MurangaAllSectorsSeeder extends Seeder
{
    public function run(): void
    {
        $county = County::where('slug', 'muranga')->first();
        if (!$county) {
            $this->command->error('Muranga county not found.');
            return;
        }

        $role = Role::findOrCreate('institution_admin', 'web');
        $this->command->info('Murang\'a All-Sectors institutions:');

        $definitions = [
            // ─── EDUCATION ─────────────────────────────────────────────
            [
                'slug' => 'muranga-university-of-science-and-technology',
                'name' => 'Murang\'a University of Science and Technology',
                'type' => 'Public University',
                'description' => 'A public research university powering tea, coffee and agri-tech value chains — with a signature 4D holographic display at the main gate.',
                'location' => 'Murang\'a Town, Murang\'a-Kenol Road',
                'phone' => '+254 733 000 000',
                'email' => 'info@murangauniversity.ac.ke',
                'website' => 'https://murangauniversity.ac.ke',
                'founded_year' => 2011,
                'lat' => -0.7182, 'lng' => 37.1482,
                'story' => "Murang'a University of Science and Technology is Central Kenya's innovation engine for the tea and coffee economy. Beyond lecture halls, the campus is famous for its 4D holographic display — a futuristic welcome that hints at the technology packed inside. Students research the full tea value chain, from leaf to market, right in the world's historic highland tea country.",
                'sector' => 'education',
                'mapping' => [
                    'sector_slug' => 'education',
                    'entry_name' => 'Murang\'a University of Science & Technology — Campus Tour',
                    'entry_type' => 'University Campus',
                    'entry_fee' => 0,
                    'location' => 'Murang\'a Town, Murang\'a-Kenol Road',
                    'description' => 'Tour a working research university in Kenya\'s historic tea highlands — see the 4D holographic display, agri-tech labs, and student innovation hubs.',
                ],
            ],
            [
                'slug' => 'muranga-institute-of-technology',
                'name' => 'Murang\'a Institute of Technology',
                'type' => 'Technical & Vocational Institute',
                'description' => 'Hands-on TVET institute in engineering, ICT, hospitality and business — a launchpad for Murang\'a\'s next generation of builders and makers.',
                'location' => 'Kangari Road, Murang\'a County',
                'phone' => '+254 735 000 000',
                'email' => 'admissions@mitmuranga.ac.ke',
                'founded_year' => 1982,
                'lat' => -0.7550, 'lng' => 37.1100,
                'story' => "Murang'a Institute of Technology trains the makers, welders, coders and chefs driving the local economy. Workshops, computer labs and a demonstration farm give every student a working start. It's where hands-on talent meets the highlands' agricultural heart.",
                'sector' => 'education',
                'mapping' => [
                    'sector_slug' => 'education',
                    'entry_name' => 'Murang\'a Institute of Technology — Workshop & Maker Tour',
                    'entry_type' => 'TVET Institute',
                    'entry_fee' => 0,
                    'location' => 'Kangari Road, Murang\'a County',
                    'description' => 'Meet the engineers and makers behind Murang\'a\'s skilled workforce — workshops, ICT labs, and a demonstration farm that doubles as a teaching garden.',
                ],
            ],

            // ─── HEALTH ────────────────────────────────────────────────
            [
                'slug' => 'muranga-county-referral-hospital',
                'name' => 'Murang\'a County Referral Hospital',
                'type' => 'County Referral Hospital (Level 5)',
                'description' => 'The county\'s flagship Level 5 hospital — comprehensive care, surgical services, maternity, ICU and a modern radiology wing serving 1M+ residents.',
                'location' => 'Murang\'a Town, Hospital Road',
                'phone' => '+254 736 000 000',
                'email' => 'info@murangareferral.go.ke',
                'website' => 'https://muranga.go.ke',
                'founded_year' => 1975,
                'lat' => -0.7205, 'lng' => 37.1528,
                'story' => "Serving more than a million people across the highlands, Murang'a County Referral is the medical anchor of the region — from life-saving surgery and ICU to maternal and child health, TB/HIV comprehensive care, and a busy radiology department.",
                'sector' => 'health',
                'mapping' => [
                    'sector_slug' => 'health',
                    'entry_name' => 'Murang\'a County Referral Hospital',
                    'entry_type' => 'Level 5 Hospital',
                    'entry_fee' => 0,
                    'location' => 'Murang\'a Town, Hospital Road',
                    'description' => 'The county\'s flagship referral hospital — specialist outpatient care, surgical theatres, ICU, maternity and comprehensive care under one roof.',
                ],
            ],

            // ─── TECHNOLOGY ────────────────────────────────────────────
            [
                'slug' => 'muranga-agri-tech-innovation-hub',
                'name' => 'Murang\'a Agri-Tech Innovation Hub',
                'type' => 'Technology & Innovation Hub',
                'description' => 'A collaborative hub where farmers, startups and researchers build digital tools for tea, coffee, avocado and macadamia value chains.',
                'location' => 'Murang\'a Town, near the University',
                'phone' => '+254 744 000 000',
                'email' => 'hub@muranga.go.ke',
                'founded_year' => 2021,
                'lat' => -0.7170, 'lng' => 37.1490,
                'story' => "Imagine precision irrigation dashboards, market-price apps and drone crop scouts — all built in Murang'a by farmers and coders side by side. The Agri-Tech hub is where the highlands' oldest industry meets its newest tools.",
                'sector' => 'technology',
                'mapping' => [
                    'sector_slug' => 'technology',
                    'entry_name' => 'Murang\'a Agri-Tech Innovation Hub',
                    'entry_type' => 'Innovation Hub',
                    'entry_fee' => 0,
                    'location' => 'Murang\'a Town',
                    'description' => 'See agri-tech startups building real tools for farmers — drone monitoring, market intelligence, and farm-management apps in a live coworking hive.',
                ],
            ],

            // ─── MANUFACTURING ─────────────────────────────────────────
            [
                'slug' => 'alba-foods-macadamia-processing',
                'name' => 'Alba Foods — Macadamia Processing Plant',
                'type' => 'Food Processing Plant',
                'description' => 'A modern, ISO 22000-certified macadamia plant sourcing from 2,000+ smallholder farmers — cracking, roasting and packing for export.',
                'location' => 'Murang\'a Town Industrial Area',
                'phone' => '+254 732 000 000',
                'email' => 'sales@albafoods.co.ke',
                'founded_year' => 2015,
                'lat' => -0.7280, 'lng' => 37.1560,
                'story' => "Two thousand highland farmers feed Alba Foods' gleaming processing line, where raw Murang'a-20 nuts become roasted kernels, oil and export packs. Watch the cracking-to-carton journey — Kenya's gold-crusted nut, upgraded in Murang'a.",
                'sector' => 'manufacturing',
                'mapping' => [
                    'sector_slug' => 'manufacturing',
                    'entry_name' => 'Alba Foods Macadamia Processing Tour',
                    'entry_type' => 'Food Processing Facility',
                    'entry_fee' => 300,
                    'location' => 'Murang\'a Town Industrial Area',
                    'description' => 'A working macadamia plant where 2,000+ farmers\' harvest becomes export-grade kernels, roasted nuts and oil — see cracking, grading, roasting and packing up close.',
                ],
            ],
            [
                'slug' => 'muranga-coffee-and-tea-factory-outlets',
                'name' => 'Murang\'a Coffee & Tea Factory Cooperative',
                'type' => 'Processing Cooperative',
                'description' => 'A cooperative of 8,000+ smallholder farmers running modern coffee pulperies and tea factories — from wet mill to 85+ point specialty score.',
                'location' => 'Maragua & Kangari zones, Murang\'a County',
                'phone' => '+254 745 000 000',
                'email' => 'info@murangaco.op.ke',
                'founded_year' => 1960,
                'lat' => -0.7800, 'lng' => 37.0500,
                'story' => "Across Murang'a's emerald ridges, thousands of smallholder families hand-pick coffee and tea that routinely scores 85+ in cupping. The cooperative runs the wet mills, drying beds and tea factories that have made Murang'a a specialty-buyer destination.",
                'sector' => 'manufacturing',
                'mapping' => [
                    'sector_slug' => 'manufacturing',
                    'entry_name' => 'Murang\'a Coffee Wet-Mill & Tea Factory Tour',
                    'entry_type' => 'Factory Cooperative',
                    'entry_fee' => 250,
                    'location' => 'Maragua & Kangari zones, Murang\'a County',
                    'description' => 'Walk a working coffee wet-mill and tea factory — follow the cherry-to-cup and leaf-to-packet journey of Murang\'a\'s famous 85+ point specialty grades.',
                ],
            ],

            // ─── TRANSPORT ─────────────────────────────────────────────
            [
                'slug' => 'muranga-town-transport-hub',
                'name' => 'Murang\'a Town — Transport & Logistics Hub',
                'type' => 'Transport & Logistics Gateway',
                'description' => 'The gateway to the highlands — hourly shuttles to Nairobi, a vibrant matatu network, and a busy stop on the A2 highway feeding the tea and coffee economy.',
                'location' => 'Murang\'a Town Centre (A2 Highway)',
                'phone' => '+254 741 000 000',
                'email' => 'transport@muranga.go.ke',
                'website' => 'https://muranga.go.ke',
                'lat' => -0.7172, 'lng' => 37.1495,
                'story' => "Every highland journey starts here. Murang'a Town is the freight and passenger crossroads of the A2 — where Nairobi-bound shuttles queue beside rattling matatus and lorries hauling avocados, macadamia and milk to market.",
                'sector' => 'transport',
                'mapping' => [
                    'sector_slug' => 'transport',
                    'entry_name' => 'Murang\'a Town Transport Hub',
                    'entry_type' => 'Transport Gateway',
                    'entry_fee' => 0,
                    'location' => 'Murang\'a Town Centre (A2 Highway)',
                    'description' => 'The highlands\' logistics heart — hourly Nairobi shuttles, county matatu networks and the produce-freight artery that moves Murang\'a\'s harvests to market.',
                ],
            ],

            // ─── FINANCE ───────────────────────────────────────────────
            [
                'slug' => 'muranga-farmers-sacco',
                'name' => 'Murang\'a Farmers Sacco Society',
                'type' => 'Saving & Credit Cooperative (SACCO)',
                'description' => 'The savings heart of the highlands — financing tea, coffee and avocado farmers since the 1970s, with digital lending now reaching every village.',
                'location' => 'Murang\'a Town, Bank Street',
                'phone' => '+254 746 000 000',
                'email' => 'info@murangasacco.co.ke',
                'founded_year' => 1974,
                'lat' => -0.7195, 'lng' => 37.1480,
                'story' => "Before the modern banks, the SACCO was the highlands' bank. Murang'a Farmers SACCO has bankrolled generations of tea and coffee smallholders, and today blends that heritage with mobile and digital lending.",
                'sector' => 'finance',
                'mapping' => [
                    'sector_slug' => 'finance',
                    'entry_name' => 'Murang\'a Farmers Sacco — Cooperative Banking Tour',
                    'entry_type' => 'SACCO / Cooperative Finance',
                    'entry_fee' => 0,
                    'location' => 'Murang\'a Town, Bank Street',
                    'description' => 'Discover the cooperative bank that grew from a farmers\' fund into a digital lender financing whole tea, coffee and avocado communities.',
                ],
            ],

            // ─── CREATIVE ECONOMY ──────────────────────────────────────
            [
                'slug' => 'muranga-creative-arts-and-textiles',
                'name' => 'Murang\'a Creative Arts & Textiles Co-op',
                'type' => 'Creative Economy Cooperative',
                'description' => 'Master weavers and artisans hand-crafting baskets, sisal textiles, and cultural pieces — the creative soul of the Agikuyu highlands.',
                'location' => 'Murang\'a Town Craft Market & Kangari',
                'phone' => '+254 747 000 000',
                'email' => 'crafts@muranga.go.ke',
                'lat' => -0.7220, 'lng' => 37.1490,
                'story' => "In the market corners and village looms of Murang'a, women and men weave the Agikuyu story into handcrafted baskets, sisal bags and ceremonial textiles. Every piece carries a pattern passed down through generations — the county's original creative economy.",
                'sector' => 'creative',
                'mapping' => [
                    'sector_slug' => 'creative',
                    'entry_name' => 'Murang\'a Handwoven Baskets & Textiles',
                    'entry_type' => 'Arts & Crafts Cooperative',
                    'entry_fee' => 0,
                    'location' => 'Murang\'a Town Craft Market',
                    'description' => 'Watch master weavers turn sisal and reeds into heirloom baskets and textiles — buy direct from the artisans who keep the highlands\' craft heritage alive.',
                ],
            ],

            // ─── REAL ESTATE & CONSTRUCTION ────────────────────────────
            [
                'slug' => 'muranga-sagana-riverside-estates',
                'name' => 'Murang\'a Sagana Riverside Estates',
                'type' => 'Real Estate & Eco-Development',
                'description' => 'Ruby-red soil meets the Sagana River — a growing belt of riverside cottages, camps and homestead estates minutes from the A2 highway.',
                'location' => 'Sagana River Valley, Murang\'a County',
                'phone' => '+254 748 000 000',
                'email' => 'sales@sagariverside.co.ke',
                'founded_year' => 2018,
                'lat' => -0.6900, 'lng' => 37.2000,
                'story' => "The Sagana River valley is Murang'a's new address. Cool, fertile and 90 minutes from Nairobi, it's sprouting riverside cottages, river-view camps and eco-estates — where weekend-home buyers come for the sound of running water.",
                'sector' => 'real_estate',
                'mapping' => [
                    'sector_slug' => 'real_estate',
                    'entry_name' => 'Sagana Riverside Estates & Eco-Development',
                    'entry_type' => 'Real Estate / Eco-Estate',
                    'entry_fee' => 0,
                    'location' => 'Sagana River Valley, Murang\'a County',
                    'description' => 'Explore Murang\'a\'s rising riverside property belt — eco-cottages and riverfront estates on the Sagana, 90 minutes from Nairobi.',
                ],
            ],

            // ─── AGRICULTURE (complementary) ───────────────────────────
            [
                'slug' => 'kangari-tea-smallholder-cooperative',
                'name' => 'Kangari Tea Smallholder Farmers Cooperative',
                'type' => 'Tea Farm Cooperative',
                'description' => '5,000+ smallholder tea families feeding one of Central Kenya\'s most productive factories — the beating heart of the Kangari tea zone.',
                'location' => 'Kangari Highlands, Murang\'a County',
                'phone' => '+254 749 000 000',
                'email' => 'kangaritea@coop.ke',
                'founded_year' => 1965,
                'lat' => -0.7500, 'lng' => 37.0800,
                'story' => "Five thousand families, one shared factory, and an ocean of green. The Kangari cooperative's members hand-pick tea on foggy highland slopes and deliver to a factory that processes over 10 million kilos of green leaf a year.",
                'sector' => 'agriculture',
                'mapping' => [
                    'sector_slug' => 'agriculture',
                    'entry_name' => 'Kangari Tea Zone Smallholder Tour',
                    'entry_type' => 'Tea Cooperative',
                    'entry_fee' => 0,
                    'location' => 'Kangari Highlands, Murang\'a County',
                    'description' => 'Meet the 5,000-family cooperative behind Kangari\'s tea empire — walk the green-leaf delivery lanes, factory floor and the farmers\' own story.',
                ],
            ],
        ];

        $count = 0;
        foreach ($definitions as $def) {
            $data = $this->toInstitutionData($county, $def);
            $institution = CountyInstitution::where('slug', $def['slug'])->first();
            if ($institution) {
                $institution->update($data);
            } else {
                $institution = CountyInstitution::create(array_merge($data, ['county_id' => $county->id]));
            }

            try {
                $summary = app(InstitutionSyncService::class)->sync($institution);
                $this->command->info("    ✓ {$def['name']} -> {$def['sector']} (" . ($summary['sectors'] ?? 0) . " sector links)");
                $count++;
            } catch (\Throwable $e) {
                $this->command->error("    ✗ {$def['name']} sync failed: " . $e->getMessage());
            }
        }

        $this->command->info("Done — {$count} institutions covering all sectors.");
    }

    protected function toInstitutionData(County $county, array $def): array
    {
        return [
            'slug' => $def['slug'],
            'user_id' => $this->ensureInstitutionUser($county, $def['name']),
            'name' => $def['name'],
            'type' => $def['type'],
            'description' => $def['description'],
            'location' => $def['location'],
            'headquarters' => $def['location'],
            'phone' => $def['phone'] ?? null,
            'email' => $def['email'] ?? null,
            'website' => $def['website'] ?? null,
            'founded_year' => $def['founded_year'] ?? null,
            'lat' => $def['lat'] ?? null,
            'lng' => $def['lng'] ?? null,
            'is_published' => true,
            'story' => $def['story'],
            'sector_mappings' => [$def['mapping']],
        ];
    }

    protected function ensureInstitutionUser(County $county, string $name): ?int
    {
        $email = 'institution.' . Str::slug($name) . '@kicctest.org';
        $admin = User::where('email', $email)->first();
        if (!$admin) {
            $admin = User::create([
                'name' => $name,
                'email' => $email,
                'password' => bcrypt(Str::random(24)),
                'account_type' => 'institution',
                'county_id' => $county->id,
                'status' => 'active',
            ]);
            $admin->assignRole('institution_admin');
        }
        return $admin->id;
    }
}