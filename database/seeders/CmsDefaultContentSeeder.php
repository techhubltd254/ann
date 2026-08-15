<?php

namespace Database\Seeders;

use App\Models\FaqItem;
use App\Models\Page;
use App\Models\ServiceItem;
use App\Models\TeamMember;
use App\Models\TimelineEvent;
use App\Models\VideoItem;
use Illuminate\Database\Seeder;

class CmsDefaultContentSeeder extends Seeder
{
    public function run(): void
    {
        // ─── PAGES ───
        $pages = [
            ['slug' => 'about', 'title' => 'About KICC', 'category' => 'about', 'sort_order' => 1, 'content' => '<p>The Kenyatta International Convention Centre (KICC) marked its 50th anniversary in September 2023. The magnificent masterpiece has been and still remains the country\'s icon and symbol since its inception in 1973. The Centre forms part of Kenya\'s history and a companion that defines and shapes the country\'s future.</p><p>It is the symbol of Kenya\'s capital, a symbol of socio-economic power for Kenya within the region. It is a source of identity and a national treasure of the Kenyan people.</p>'],
            ['slug' => 'mission', 'title' => 'Mission, Vision & Mandate', 'category' => 'about', 'sort_order' => 2, 'content' => '<h2>Vision</h2><p>To be Africa\'s leading convention centre and a world-class destination for meetings, incentives, conferences and exhibitions.</p><h2>Mission</h2><p>To provide exceptional conference and event experiences that exceed expectations, contribute to Kenya\'s tourism growth, and position KICC as Africa\'s premier meeting venue.</p><h2>Mandate</h2><ul><li>Promote and position Kenya as a preferred MICE destination</li><li>Provide world-class conference and exhibition facilities</li><li>Generate revenue through venue hire and related services</li><li>Support Kenya\'s tourism and economic development goals</li></ul>'],
            ['slug' => 'board', 'title' => 'KICC Board', 'category' => 'about', 'sort_order' => 3, 'content' => '<p>The KICC Board provides strategic leadership and oversight for Africa\'s premier convention centre.</p>'],
            ['slug' => 'management', 'title' => 'KICC Management', 'category' => 'about', 'sort_order' => 4, 'content' => '<p>The KICC management team ensures world-class service delivery across all venues and services.</p>'],
            ['slug' => 'history', 'title' => 'KICC History', 'category' => 'about', 'sort_order' => 5, 'content' => '<p>KICC marked its 50th anniversary in September 2023. The magnificent masterpiece has been and still remains the country\'s icon and symbol since its inception in 1973.</p>'],
            ['slug' => 'pricing', 'title' => 'Pricing Guideline', 'category' => 'services', 'sort_order' => 1, 'content' => '<p>KICC offers competitive rates for its world-class facilities. For detailed pricing, contact our sales team.</p>'],
            ['slug' => 'sustainability', 'title' => 'Sustainability', 'category' => 'info', 'sort_order' => 1, 'content' => '<p>KICC is committed to sustainable practices in all our operations. We continuously work to reduce our environmental impact through energy efficiency, waste management, and responsible sourcing.</p>'],
            ['slug' => 'visitor-facilities', 'title' => 'Visitor Facilities', 'category' => 'info', 'sort_order' => 2, 'content' => '<p>KICC offers comprehensive visitor facilities including wheelchair accessibility, parking, information desks, first aid, and catering outlets throughout the venue.</p>'],
            ['slug' => 'transport', 'title' => 'Transport', 'category' => 'info', 'sort_order' => 3, 'content' => '<p>KICC is located at Harambee Avenue, Nairobi CBD. Accessible by road, taxi, and public transport. Secure on-site parking available.</p>'],
            ['slug' => 'helipad', 'title' => 'Helipad Services', 'category' => 'services', 'sort_order' => 2, 'content' => '<p>KICC features a rooftop helipad offering convenient helicopter access to and from the convention centre. Ideal for VIP arrivals and executive travel.</p>'],
            ['slug' => 'places-to-stay', 'title' => 'Places to Stay', 'category' => 'info', 'sort_order' => 4, 'content' => '<p>Nairobi offers a wide range of accommodation options near KICC, from luxury hotels to budget-friendly guesthouses. Our team can assist with recommendations.</p>'],
            ['slug' => 'opportunities', 'title' => 'Business Opportunities', 'category' => 'info', 'sort_order' => 5, 'content' => '<p>KICC offers partnership and business opportunities for vendors, suppliers, and service providers. Contact our procurement team for current opportunities.</p>'],
            ['slug' => 'policy-documents', 'title' => 'Policy Documents', 'category' => 'info', 'sort_order' => 6, 'content' => '<p>Access KICC policy documents, terms and conditions, and regulatory compliance information.</p>'],
            ['slug' => 'annual-reports', 'title' => 'Annual Reports', 'category' => 'info', 'sort_order' => 7, 'content' => '<p>Download KICC annual reports and financial statements.</p>'],
            ['slug' => 'publications', 'title' => 'Publications', 'category' => 'info', 'sort_order' => 8, 'content' => '<p>KICC publications, brochures, and information materials.</p>'],
            ['slug' => 'service-charter', 'title' => 'Service Charter', 'category' => 'about', 'sort_order' => 6, 'content' => '<p>KICC is committed to providing quality services to all our clients. Our service charter outlines the standards you can expect.</p><ul><li>Timely response to enquiries within 24 hours</li><li>Professional and courteous service</li><li>Clean and well-maintained facilities</li><li>Transparent pricing</li><li>Accessible facilities for all</li></ul>'],
            ['slug' => 'our-departments', 'title' => 'Our Departments', 'category' => 'about', 'sort_order' => 7, 'content' => '<p>KICC is organized into several departments working together to deliver world-class service.</p><ul><li>Sales & Marketing</li><li>Events & Operations</li><li>Finance & Administration</li><li>Technical Services</li><li>Catering & Hospitality</li><li>Security & Safety</li><li>Human Resources</li><li>Engineering & Maintenance</li></ul>'],
        ];
        foreach ($pages as $p) {
            Page::firstOrCreate(['slug' => $p['slug']], $p);
        }

        // ─── TEAM MEMBERS ───
        TeamMember::firstOrCreate(['name' => 'CPA Samuel Waweru Mwangi', 'category' => 'board'], ['title' => 'Chairperson', 'bio' => 'A seasoned finance professional with extensive experience in accounting, financial management, and operations. Holds a Master of Science in Finance and Investment. Certified Public Accountant and Certified Investment and Financial Analyst (CIFA). Member of ICPAK.', 'sort_order' => 1]);
        TeamMember::firstOrCreate(['name' => 'KICC Management Team', 'category' => 'management'], ['title' => 'Management', 'bio' => 'The KICC management team is dedicated to maintaining the highest standards of service excellence across all venue operations.', 'sort_order' => 1]);

        // ─── LEADERSHIP ───
        $leadership = [
            ['name' => 'CPA Samuel Waweru Mwangi', 'category' => 'board', 'title' => 'Chairperson', 'bio' => 'Seasoned finance professional with extensive experience in accounting, financial management, and operations.', 'sort_order' => 1],
            ['name' => 'James Ochieng\'', 'category' => 'leadership', 'title' => 'Chief Executive Officer', 'bio' => 'Leading KICC\'s vision as Africa\'s premier convention centre.', 'sort_order' => 1],
            ['name' => 'Grace Nyambura', 'category' => 'leadership', 'title' => 'Director, Sales & Marketing', 'bio' => 'Driving revenue growth and market positioning for KICC.', 'sort_order' => 2],
            ['name' => 'Peter Kamau', 'category' => 'leadership', 'title' => 'Director, Events & Operations', 'bio' => 'Ensuring world-class event delivery across all venues.', 'sort_order' => 3],
            ['name' => 'Dr. Mary Atieno', 'category' => 'leadership', 'title' => 'Director, Finance & Administration', 'bio' => 'Overseeing financial strategy and administrative operations.', 'sort_order' => 4],
        ];
        foreach ($leadership as $l) {
            TeamMember::firstOrCreate(['name' => $l['name']], ['is_active' => true] + $l);
        }

        // ─── TIMELINE EVENTS ───
        $events = [
            ['year' => '1973', 'title' => 'KICC Inception', 'description' => 'Inception of KICC as a national icon.', 'sort_order' => 1],
            ['year' => '1985', 'title' => 'KICC Expansion', 'description' => 'Major expansion of KICC facilities.', 'sort_order' => 2],
            ['year' => '2007', 'title' => 'Prestigious Award', 'description' => 'Best Convention Center in Africa award.', 'sort_order' => 3],
            ['year' => '2023', 'title' => '50th Anniversary', 'description' => 'KICC celebrated 50 years of service.', 'sort_order' => 4],
        ];
        foreach ($events as $e) {
            TimelineEvent::firstOrCreate(['year' => $e['year'], 'title' => $e['title']], $e);
        }

        // ─── FAQ ───
        $faqs = [
            ['question' => 'Which Ministry does KICC fall under?', 'answer' => 'KICC is a state corporation under the Ministry of Tourism and is responsible for promoting and positioning Kenya as a preferred meetings, incentives, conferences/conventions and exhibition destination.', 'sort_order' => 1],
            ['question' => 'How do I book an event at KICC?', 'answer' => 'You can book an event through our online event booking form at /kicc/event-booking or call our sales team at (+254) 20 3261000.', 'sort_order' => 2],
            ['question' => 'What venues are available?', 'answer' => 'KICC offers Tsavo Hall, Amphitheatre, Aberdares, Lenana Hills, Shimba Hills Room, Courtyard, Lawn, Upper COMESA, Lower COMESA, and more.', 'sort_order' => 3],
            ['question' => 'Does KICC offer catering?', 'answer' => 'Yes, KICC provides in-house catering services for all events, from coffee breaks to state banquets.', 'sort_order' => 4],
            ['question' => 'Is there parking at KICC?', 'answer' => 'Yes, secure on-site parking is available for guests and exhibitors.', 'sort_order' => 5],
        ];
        foreach ($faqs as $f) {
            FaqItem::firstOrCreate(['question' => $f['question']], $f + ['is_published' => true]);
        }

        // ─── SERVICE ITEMS ───
        $services = [
            ['title' => 'Audio-Visual Equipment', 'description' => 'PA systems, screens, staging and live-streaming gear for all event types.', 'icon' => '🎧', 'sort_order' => 1],
            ['title' => 'Catering Services', 'description' => 'In-house catering from coffee breaks to state banquets, tailored to your event.', 'icon' => '🍽️', 'sort_order' => 2],
            ['title' => 'Event Planning & Coordination', 'description' => 'Dedicated coordinators to manage every detail from booking to closing.', 'icon' => '📋', 'sort_order' => 3],
            ['title' => 'Technical Support', 'description' => 'On-site technical staff for the entire duration of your event.', 'icon' => '🔧', 'sort_order' => 4],
            ['title' => 'Wi-Fi & Internet Access', 'description' => 'High-density venue Wi-Fi capable of serving thousands of delegates.', 'icon' => '📶', 'sort_order' => 5],
            ['title' => 'Security Services', 'description' => '24/7 security, screening, and fire safety compliance.', 'icon' => '🛡️', 'sort_order' => 6],
            ['title' => 'Parking Facilities', 'description' => 'Secure on-site parking for guests, exhibitors, and VIPs.', 'icon' => '🅿️', 'sort_order' => 7],
            ['title' => 'Accessibility', 'description' => 'Step-free access, lifts, and accessible facilities throughout the venue.', 'icon' => '♿', 'sort_order' => 8],
            ['title' => 'Tourist Information', 'description' => 'Visitor desk with city guides, safari information, and travel assistance.', 'icon' => '🗺️', 'sort_order' => 9],
        ];
        foreach ($services as $s) {
            ServiceItem::firstOrCreate(['title' => $s['title']], $s + ['is_published' => true]);
        }

        // ─── VIDEO ITEMS ───
        $videos = [
            ['title' => 'KICC Welcome', 'description' => 'A tour of Africa\'s premier meeting venue.', 'video_url' => 'https://www.youtube.com/watch?v=example1', 'sort_order' => 1],
            ['title' => 'KICC Venues', 'description' => 'Explore our world-class facilities.', 'video_url' => 'https://www.youtube.com/watch?v=example2', 'sort_order' => 2],
            ['title' => 'KICC Events', 'description' => 'Highlights from recent events at KICC.', 'video_url' => 'https://www.youtube.com/watch?v=example3', 'sort_order' => 3],
        ];
        foreach ($videos as $v) {
            VideoItem::firstOrCreate(['title' => $v['title']], $v + ['is_published' => true]);
        }

        $this->command?->info('CMS default content seeded: ' . count($pages) . ' pages, team, timeline, ' . count($faqs) . ' FAQs, ' . count($services) . ' services, ' . count($videos) . ' videos.');
    }
}