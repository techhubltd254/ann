<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'reviewable_type' => 'required|string',
            'reviewable_id' => 'required|integer',
            'rating' => 'required|integer|min:1|max:5',
            'content' => 'nullable|string|max:2000',
            'photos' => 'nullable|array',
            'photos.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $review = Review::create([
            'user_id' => $request->user()?->id,
            'reviewable_type' => $data['reviewable_type'],
            'reviewable_id' => $data['reviewable_id'],
            'rating' => $data['rating'],
            'content' => $data['content'] ?? null,
            'status' => 'pending',
        ]);

        if ($request->hasFile('photos')) {
            $paths = [];
            foreach ($request->file('photos') as $photo) {
                $paths[] = $photo->store('reviews/' . $review->id, 'public');
            }
            $review->update(['photos' => $paths]);
        }

        return back()->with('success', 'Review submitted and pending moderation.');
    }

    public function updateVendorResponse(Request $request, Review $review)
    {
        $data = $request->validate(['vendor_response' => 'required|string|max:2000']);
        $review->update(['vendor_response' => $data['vendor_response'], 'responded_at' => now()]);
        return back()->with('success', 'Response submitted.');
    }
}

class ReviewAdminController extends Controller
{
    public function __construct() { $this->middleware('auth'); }

    public function index(Request $request)
    {
        $query = Review::with('reviewable', 'user')->latest();
        $status = $request->get('status');
        if ($status) $query->where('status', $status);
        $reviews = $query->paginate(25);
        return view('reviews.admin-index', compact('reviews', 'status'));
    }

    public function approve(Request $request, Review $review)
    {
        $review->update(['status' => 'approved', 'is_verified' => $request->has('verified')]);
        \App\Services\N8nService::fire('review_approved', ['review_id' => $review->id]);
        return back()->with('success', 'Review approved.');
    }

    public function reject(Request $request, Review $review)
    {
        $review->update(['status' => 'rejected']);
        return back()->with('success', 'Review rejected.');
    }
}