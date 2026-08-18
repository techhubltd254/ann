<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SponsorSeeder extends Seeder
{
    public function run(): void
    {
        try {
            $advertisers = [
                ['company_name' => 'Safaricom PLC', 'industry' => 'Telecommunications', 'website' => 'https://www.safaricom.co.ke'],
                ['company_name' => 'KCB Group PLC', 'industry' => 'Banking & Finance', 'website' => 'https://www.kcbgroup.com'],
                ['company_name' => 'Kenya Airways PLC', 'industry' => 'Aviation', 'website' => 'https://www.kenya-airways.com'],
                ['company_name' => 'Equity Group Holdings', 'industry' => 'Banking & Finance', 'website' => 'https://equitygroupholdings.com'],
                ['company_name' => 'Kenya Tourism Board', 'industry' => 'Tourism', 'website' => 'https://www.magicalkenya.com'],
                ['company_name' => 'Absa Bank Kenya PLC', 'industry' => 'Banking & Finance', 'website' => 'https://www.absa.co.ke'],
                ['company_name' => 'Britam Holdings PLC', 'industry' => 'Insurance', 'website' => 'https://www.britam.com'],
                ['company_name' => 'Coca-Cola Beverages Africa', 'industry' => 'Beverages', 'website' => 'https://www.coca-cola.com'],
                ['company_name' => 'Telkom Kenya', 'industry' => 'Telecommunications', 'website' => 'https://www.telkom.co.ke'],
                ['company_name' => 'Jubilee Insurance', 'industry' => 'Insurance', 'website' => 'https://www.jubileeinsurance.com'],
            ];

            $headlines = [
                'Discover the Future of Tech', 'Partner with KICC for Global Reach',
                'Exclusive Exhibition Sponsorship', 'Connect with Industry Leaders',
                'Showcase Your Brand to Thousands', 'Premium Booth Placement Available',
                'Be Part of Kenya\'s Growth Story', 'Unlock Business Opportunities',
                'Network with Decision Makers', 'Amplify Your Brand at KICC',
            ];
            $descriptions = [
                'Reach thousands of exhibition visitors and showcase your brand at Kenya\'s premier event venue.',
                'Sponsor the next major exhibition at KICC and connect with industry leaders from across Africa.',
                'Premium sponsorship packages available for the upcoming international trade fair.',
                'Get unparalleled visibility with our exhibition sponsorship program.',
            ];
            $ctas = ['Learn More', 'Book Now', 'Get Offer', 'Register', 'Visit Site'];
            $types = ['banner', 'video', 'carousel'];
            $objectives = ['brand_awareness', 'event_promotion', 'lead_generation'];
            $dimensions = [[728, 90], [300, 250], [160, 600], [320, 100]];

            $now = now();
            $placements = DB::table('ad_placements')->where('is_active', '>', 0)->pluck('id')->toArray();
            if (empty($placements)) {
                $placements = [1];
            }

            $advertiserIds = [];
            $userIds = DB::table('users')->pluck('id')->toArray();
            foreach ($advertisers as $i => $a) {
                $userId = $userIds[$i] ?? $userIds[0];
                $id = DB::table('advertisers')->insertGetId([
                    'user_id' => $userId,
                    'company_name' => $a['company_name'],
                    'industry' => $a['industry'],
                    'website' => $a['website'],
                    'status' => 'active',
                    'account_balance' => 0,
                    'credit_limit' => 1000000,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                DB::table('advertisers')->where('id', $id)->update([
                    'account_balance' => round(mt_rand(50000, 500000) / 100, 2),
                ]);
                $advertiserIds[] = $id;
            }

            foreach ($advertiserIds as $advId) {
                $count = mt_rand(1, 2);
                for ($c = 0; $c < $count; $c++) {
                    $campName = $headlines[array_rand($headlines)];
                    $campId = DB::table('ad_campaigns')->insertGetId([
                        'advertiser_id' => $advId,
                        'name' => $campName . ' Campaign',
                        'slug' => Str::slug($campName . '-' . uniqid()),
                        'objective' => $objectives[array_rand($objectives)],
                        'status' => 'active',
                        'start_date' => $now->copy()->subDays(mt_rand(0, 30)),
                        'end_date' => $now->copy()->addDays(mt_rand(30, 90)),
                        'daily_budget' => round(mt_rand(5000, 50000) / 100, 2),
                        'total_budget' => round(mt_rand(100000, 2000000) / 100, 2),
                        'currency' => 'KES',
                        'spent' => 0,
                        'target_impressions' => mt_rand(10000, 100000),
                        'target_clicks' => mt_rand(500, 5000),
                        'optimization_goal' => 'click',
                        'is_cpm' => mt_rand(0, 1),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    $gc = mt_rand(1, 2);
                    for ($g = 0; $g < $gc; $g++) {
                        $gId = DB::table('ad_groups')->insertGetId([
                            'campaign_id' => $campId,
                            'name' => 'Group ' . ($g + 1) . ' — ' . $headlines[array_rand($headlines)],
                            'bid_amount' => round(mt_rand(500, 5000) / 100, 2),
                            'bid_strategy' => ['auto', 'manual'][array_rand(['auto', 'manual'])],
                            'budget' => round(mt_rand(50000, 500000) / 100, 2),
                            'status' => 'active',
                            'start_date' => $now->copy()->subDays(mt_rand(0, 15)),
                            'end_date' => $now->copy()->addDays(mt_rand(30, 90)),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);

                        $cc = mt_rand(1, 3);
                        for ($cr = 0; $cr < $cc; $cr++) {
                            $hl = $headlines[array_rand($headlines)];
                            $dim = $dimensions[array_rand($dimensions)];
                            DB::table('ad_creatives')->insert([
                                'ad_group_id' => $gId,
                                'name' => $hl,
                                'type' => $types[array_rand($types)],
                                'headline' => $hl,
                                'description' => $descriptions[array_rand($descriptions)],
                                'call_to_action' => $ctas[array_rand($ctas)],
                                'destination_url' => 'https://kicctest.org/sponsored/' . Str::slug($hl),
                                'image_url' => null,
                                'video_url' => null,
                                'width' => $dim[0],
                                'height' => $dim[1],
                                'alt_text' => $hl,
                                'status' => 'approved',
                                'created_at' => $now,
                                'updated_at' => $now,
                            ]);
                        }
                    }
                }
            }

            $this->command->info(sprintf(
                'Seeded %d advertisers, %d campaigns, %d groups, %d creatives',
                count($advertisers),
                DB::table('ad_campaigns')->count(),
                DB::table('ad_groups')->count(),
                DB::table('ad_creatives')->count(),
            ));
        } catch (\Throwable $e) {
            $this->command->error('SponsorSeeder failed: ' . $e->getMessage());
        }
    }
}