<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\EventBooking;
use App\Models\JobApplication;
use App\Models\JobListing;
use App\Models\NewsletterSubscriber;
use App\Models\Venue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class KiccWebsiteController extends Controller
{
    // ─── NEWS / BLOG ───
    public function newsIndex()
    {
        $articles = Article::published()->latest('published_at')->paginate(12);
        $categories = Article::published()->select('category')->distinct()->pluck('category');
        $featured = Article::published()->latest('published_at')->first();
        return view('kicc-website.news.index', compact('articles', 'categories', 'featured'));
    }

    public function newsShow($slug)
    {
        $article = Article::published()->where('slug', $slug)->firstOrFail();
        $related = Article::published()->where('category', $article->category)->where('id', '!=', $article->id)->take(3)->get();
        return view('kicc-website.news.show', compact('article', 'related'));
    }

    // ─── ABOUT US (delegated to CmsController) ───
    public function eventBookingForm()
    {
        $venues = Venue::orderBy('name')->get();
        return view('kicc-website.event-booking', compact('venues'));
    }

    public function eventBookingStore(Request $request)
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'organization' => 'nullable|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'event_name' => 'nullable|string|max:255',
            'event_type' => 'required|string|in:conference,seminar,workshop,other',
            'expected_attendees' => 'nullable|integer|min:1',
            'event_date' => 'nullable|date',
            'duration_days' => 'nullable|integer|min:1',
            'preferred_venue' => 'nullable|string|max:255',
            'needs_catering' => 'nullable|boolean',
            'catering_details' => 'nullable|string|max:1000',
            'needs_av' => 'nullable|boolean',
            'av_requirements' => 'nullable|string|max:1000',
            'additional_info' => 'nullable|string|max:2000',
        ]);
        $booking = EventBooking::create($data + ['needs_catering' => $request->boolean('needs_catering'), 'needs_av' => $request->boolean('needs_av')]);
        \App\Services\N8nService::fire('event_booking_created', ['reference' => $booking->reference, 'email' => $booking->email]);
        return redirect()->route('kicc.event-booking.success', $booking->reference)->with('success', 'Booking enquiry submitted.');
    }

    public function eventBookingSuccess($reference)
    {
        $booking = EventBooking::where('reference', $reference)->firstOrFail();
        return view('kicc-website.event-booking-success', compact('booking'));
    }

    // ─── JOBS ───
    public function jobs()
    {
        $jobs = JobListing::where('is_active', true)->where(function ($q) { $q->whereNull('closing_date')->orWhere('closing_date', '>=', now()); })->latest()->get();
        return view('kicc-website.jobs.index', compact('jobs'));
    }

    public function jobsShow(JobListing $job)
    {
        return view('kicc-website.jobs.show', compact('job'));
    }

    public function jobsApply(Request $request, JobListing $job)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'cover_letter' => 'nullable|string|max:2000',
            'cv' => 'nullable|file|mimes:pdf,doc,docx|max:5120',
        ]);
        $app = JobApplication::create(['job_listing_id' => $job->id, 'name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'] ?? null, 'cover_letter' => $data['cover_letter'] ?? null]);
        if ($request->hasFile('cv')) $app->update(['cv_path' => $request->file('cv')->store('jobs/cvs', 'public')]);
        return redirect()->route('kicc.jobs')->with('success', 'Application submitted.');
    }

    // ─── NEWSLETTER ───
    public function newsletterSubscribe(Request $request)
    {
        $data = $request->validate(['email' => 'required|email|unique:newsletter_subscribers,email']);
        NewsletterSubscriber::create($data);
        \App\Services\N8nService::fire('newsletter_subscribed', ['email' => $data['email']]);
        return back()->with('success', 'Subscribed! Check your inbox.');
    }

    // ─── STATIC PAGES (now DB-driven from CmsController) ───
    public function about() { return app(\App\Http\Controllers\Web\CmsController::class)->about(); }
    public function mission() { return app(\App\Http\Controllers\Web\CmsController::class)->mission(); }
    public function board() { return app(\App\Http\Controllers\Web\CmsController::class)->board(); }
    public function management() { return app(\App\Http\Controllers\Web\CmsController::class)->management(); }
    public function history() { return app(\App\Http\Controllers\Web\CmsController::class)->history(); }
    public function pricing() { return app(\App\Http\Controllers\Web\CmsController::class)->pricing(); }
    public function faq() { return app(\App\Http\Controllers\Web\CmsController::class)->faq(); }
    public function videoGallery() { return app(\App\Http\Controllers\Web\CmsController::class)->videoGallery(); }
    public function services() { return app(\App\Http\Controllers\Web\CmsController::class)->services(); }
    public function sustainability() { $page = \App\Models\Page::where('slug','sustainability')->firstOrNew([]); return view('kicc-website.sustainability', compact('page')); }
    public function visitorFacilities() { $page = \App\Models\Page::where('slug','visitor-facilities')->firstOrNew([]); return view('kicc-website.visitor-facilities', compact('page')); }
    public function transport() { $page = \App\Models\Page::where('slug','transport')->firstOrNew([]); return view('kicc-website.transport', compact('page')); }
    public function helipad() { $page = \App\Models\Page::where('slug','helipad')->firstOrNew([]); return view('kicc-website.helipad', compact('page')); }
    public function placesToStay() { $page = \App\Models\Page::where('slug','places-to-stay')->firstOrNew([]); return view('kicc-website.places-to-stay', compact('page')); }
    public function opportunities() { $page = \App\Models\Page::where('slug','opportunities')->firstOrNew([]); return view('kicc-website.opportunities', compact('page')); }
    public function policyDocuments() { $page = \App\Models\Page::where('slug','policy-documents')->firstOrNew([]); return view('kicc-website.policy-documents', compact('page')); }
    public function virtualTour() { return view('kicc-website.virtual-tour'); }
    public function orgStructure() { return view('kicc-website.org-structure'); }

    // ─── NEW KICC PAGES ───
    public function annualReports() { $page = \App\Models\Page::where('slug','annual-reports')->firstOrNew([]); return view('kicc-website.annual-reports', compact('page')); }
    public function publications() { $page = \App\Models\Page::where('slug','publications')->firstOrNew([]); return view('kicc-website.publications', compact('page')); }
    public function serviceCharter() { $page = \App\Models\Page::where('slug','service-charter')->firstOrNew([]); return view('kicc-website.service-charter', compact('page')); }
    public function leadership() { $members = \App\Models\TeamMember::where('is_active',true)->orderBy('sort_order')->get(); return view('kicc-website.leadership', compact('members')); }
    public function ourDepartments() { $page = \App\Models\Page::where('slug','our-departments')->firstOrNew([]); return view('kicc-website.our-departments', compact('page')); }

}
