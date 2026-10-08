<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\SubscriptionPlan;
use App\Events\GenericDomainEvent;
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

    /** Complexity levels for the intelligent decision tree */
    public const COMPLEXITY_LEVELS = [
        'simple'  => ['label' => 'I need a simple storefront',    'icon' => '🚀', 'desc' => 'Use a pre-made template — ready in minutes. Best for new exhibitors with standard needs.'],
        'custom'  => ['label' => 'I need a custom setup',         'icon' => '🎨', 'desc' => 'Request a personalized dashboard built by the KICC team. We will design and configure it for your business.'],
        'premium' => ['label' => 'I need the full experience',   'icon' => '🎬', 'desc' => 'Book a professional photo/video shoot. Our team visits your location, captures custom media, and builds a premium storefront.'],
    ];

    public function show()
    {
        $user = Auth::user();
        abort_unless($user?->hasAnyRole(['exhibitor', 'kicc_admin']) || $user?->account_type === 'exhibitor', 403);

        return view('experience.pages.exhibitor.onboarding', [
            'user' => $user,
            'businessTypes' => self::BUSINESS_TYPES,
            'counties' => County::orderBy('name')->get(['id', 'name']),
            'packages' => SubscriptionPlan::where('is_active', true)->orderBy('sort_order')->get(),
            'complexityLevels' => self::COMPLEXITY_LEVELS,
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
            'complexity' => 'required|in:' . implode(',', array_keys(self::COMPLEXITY_LEVELS)),
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
                'complexity' => $data['complexity'],
                'onboarded_at' => now()->toIso8601String(),
            ]),
        ]);

        event(new GenericDomainEvent('exhibitor_onboarded', [
            'user_id' => $user->id, 'business_type' => $data['business_type'],
            'package' => $package->slug, 'complexity' => $data['complexity'],
        ], n8nEventName: 'exhibitor_onboarded'));

        if ($data['complexity'] === 'custom') {
            event(new GenericDomainEvent('admin_request_created', [
                'user_id' => $user->id, 'display_name' => $data['display_name'],
                'type' => 'custom_admin', 'message' => 'Exhibitor requests a custom admin setup',
                'business_type' => $data['business_type'], 'tagline' => $data['tagline'],
            ], n8nEventName: 'admin_request_created'));
            session()->flash('success', 'Your request has been sent to the KICC team. We will build a personalized admin for you and notify you when it is ready.');
        } elseif ($data['complexity'] === 'premium') {
            event(new GenericDomainEvent('admin_request_created', [
                'user_id' => $user->id, 'display_name' => $data['display_name'],
                'type' => 'premium_shoot', 'message' => 'Exhibitor requests a premium photo/video shoot',
                'business_type' => $data['business_type'], 'tagline' => $data['tagline'],
            ], n8nEventName: 'admin_request_created'));
            session()->flash('success', 'Thank you! Our team will contact you within 24 hours to schedule your personalized photo/video shoot.');
        }

        return redirect()->route('exhibitor.admin')
            ->with('success', "🎉 Your exhibitor website is ready: " . route('exhibitor.site', Str::slug($user->name)));
    }
}
