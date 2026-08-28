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

    public function storeProduct(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:120',
            'body' => 'required|string|max:2000',
            'pros' => 'nullable|string|max:300',
            'cons' => 'nullable|string|max:300',
            'photos' => 'nullable|array',
            'photos.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $user = $request->user();
        $isVerified = false;
        if ($user) {
            $isVerified = \App\Models\Order::where('user_id', $user->id)
                ->whereHas('items', fn ($q) => $q->where('product_id', $data['product_id']))
                ->exists();
        }

        $review = \App\Models\ProductReview::create([
            'product_id' => $data['product_id'],
            'user_id' => $user?->id,
            'rating' => $data['rating'],
            'title' => $data['title'] ?? null,
            'body' => $data['body'],
            'pros' => $data['pros'] ?? null,
            'cons' => $data['cons'] ?? null,
            'is_verified_purchase' => $isVerified,
            'is_approved' => false,
        ]);

        if ($request->hasFile('photos')) {
            $paths = [];
            foreach ($request->file('photos') as $photo) {
                $paths[] = $photo->store('product-reviews/' . $review->id, 'public');
            }
            $review->update(['body' => $data['body'] . ($paths ? ' [photos]' : '')]);
        }

        return back()->with('success', 'Review submitted and pending moderation.');
    }

    public function storeEntity(Request $request)
    {
        $data = $request->validate([
            'reviewable_type' => 'required|string',
            'reviewable_id' => 'required|integer',
            'rating' => 'required|integer|min:1|max:5',
            'content' => 'required|string|max:2000',
        ]);

        \App\Models\SectorEntityReview::create([
            'sector_entity_id' => $data['reviewable_id'],
            'user_id' => $request->user()?->id,
            'rating' => $data['rating'],
            'review' => $data['content'],
            'is_verified_purchase' => false,
        ]);

        return back()->with('success', 'Review submitted.');
    }
}