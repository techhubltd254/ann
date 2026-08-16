<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Exhibitor Onboarding Wizard — 3 questions that build the exhibitor's
 * personalized website and dashboard:
 *   1. What do you sell?      (products / property / restaurant / services)
 *   2. Business details        (display name, county, tagline)
 *   3. Package                 (Free / Exhibitor Pro / County Premium / Enterprise)
 */
class ExhibitorOnboardingController extends Controller
{
    public const BUSINESS_TYPES = [
        'products'   => ['label' => 'Products & Goods',        'icon' => '🛍️', 'hint' => 'Clothes, crafts, food, produce…'],
        'property'   => ['label' => 'Property & Real Estate',  'icon' => '🏢', 'hint' => 'Studio apartments, offices, flats…'],
        'hospitality'=> ['label' => 'Hospitality & Restaurant','icon' => '🍽️', 'hint' => 'Restaurants, hotels, cafes…'],
        'services'   => ['label' => 'Services & Experiences',  'icon' => '✨', 'hint' => 'Tours, transport, events…'],
    ];

    public function show()
    {
        $user = Auth::user();
        abort_unless($user?->hasAnyRole(['exhibitor', 'kicc_admin']) || $user?->account_type === 'exhibitor', 403);

        return view('exhibitor.onboarding', [
            'user' => $user,
            'businessTypes' => self::BUSINESS_TYPES,
            'counties' => County::orderBy('name')->get(['id', 'name']),
            'packages' => SubscriptionPlan::where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        abort_unless($user?->hasAnyRole(['exhibitor', 'kicc_admin']) || $user?->account_type === 'exhibitor', 403);

        $data = $request->validate([
            'business_type' => 'required|in:' . implode(',', array_keys(self::BUSINESS_TYPES)),
            'display_name' => 'required|string|max:255',
            'county_id' => 'required|exists:counties,id',
            'tagline' => 'required|string|max:500',
            'phone' => 'required|string|max:30',
            'package_slug' => 'required|exists:subscription_plans,slug',
        ]);

        $package = SubscriptionPlan::where('slug', $data['package_slug'])->first();

        $user->update([
            'name' => $data['display_name'],
            'county_id' => $data['county_id'],
            'phone' => $data['phone'],
            'metadata' => array_merge((array) ($user->metadata ?? []), [
                'onboarding_complete' => true,
                'business_type' => $data['business_type'],
                'business_type_label' => self::BUSINESS_TYPES[$data['business_type']]['label'],
                'tagline' => $data['tagline'],
                'package' => $package->slug,
                'package_name' => $package->name,
                'onboarded_at' => now()->toIso8601String(),
            ]),
        ]);

        \App\Services\N8nService::fire('exhibitor_onboarded', [
            'user_id' => $user->id, 'business_type' => $data['business_type'], 'package' => $package->slug,
        ]);

        return redirect()->route('exhibitor.admin')
            ->with('success', "🎉 Your exhibitor website is ready: " . route('exhibitor.site', Str::slug($user->name)));
    }
}
