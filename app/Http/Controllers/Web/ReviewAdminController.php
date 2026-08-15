<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

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