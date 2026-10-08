<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;

/**
 * Public Packages page — the blueprint's subscription catalogue.
 * Exhibitor packages + county packages, all purchasable.
 */
class PackagesController extends Controller
{
    public function index()
    {
        $plans = SubscriptionPlan::where('is_active', true)->orderBy('sort_order')->get();

        $countyPackages = [
            ['name' => 'County Basic', 'price' => 10000, 'slots' => '10 bulk slots', 'features' => ['10 business logins', 'Basic county dashboard', 'County pavilion listing']],
            ['name' => 'County Premium', 'price' => 50000, 'slots' => '50 bulk slots', 'features' => ['50 business logins', 'Sub-portal & custom plans', 'Analytics dashboard', 'SEO boost']],
            ['name' => 'County Enterprise', 'price' => 200000, 'slots' => 'Unlimited slots', 'features' => ['Unlimited businesses', 'Custom domain', 'API access', 'Dedicated support']],
            ['name' => 'Corporate', 'price' => 500000, 'slots' => 'White-label', 'features' => ['White-label platform', 'Multi-county', 'AI pipeline access', 'Enterprise SLA']],
        ];

        return view('experience.pages.packages.index', compact('plans', 'countyPackages'));
    }
}
