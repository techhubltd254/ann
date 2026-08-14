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

    // ─── ABOUT US ───
    public function about() { return view('kicc-website.about'); }
    public function mission() { return view('kicc-website.mission'); }
    public function board() { return view('kicc-website.board'); }
    public function management() { return view('kicc-website.management'); }
    public function history() { return view('kicc-website.history'); }
    public function orgStructure() { return view('kicc-website.org-structure'); }

    // ─── EVENT BOOKING ───
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

    // ─── STATIC PAGES ───
    public function pricing() { return view('kicc-website.pricing'); }
    public function sustainability() { return view('kicc-website.sustainability'); }
    public function visitorFacilities() { return view('kicc-website.visitor-facilities'); }
    public function transport() { return view('kicc-website.transport'); }
    public function placesToStay() { return view('kicc-website.places-to-stay'); }
    public function helipad() { return view('kicc-website.helipad'); }
    public function virtualTour() { return view('kicc-website.virtual-tour'); }
    public function videoGallery() { return view('kicc-website.video-gallery'); }
    public function policyDocuments() { return view('kicc-website.policy-documents'); }
    public function opportunities() { return view('kicc-website.opportunities'); }
    public function faq() { return view('kicc-website.faq'); }
}