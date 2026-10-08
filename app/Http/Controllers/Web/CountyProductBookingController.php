<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\CountyProduct;
use App\Models\CountyProductBooking;
use Illuminate\Http\Request;

class CountyProductBookingController extends Controller
{
    public function show(County $county, CountyProduct $product)
    {
        abort_if($product->county_id !== $county->id, 404);
        abort_if(!$product->is_published || !$product->price, 404);

        return view('experience.pages.counties.product-booking', compact('county', 'product'));
    }

    public function book(Request $request, County $county, CountyProduct $product)
    {
        abort_if($product->county_id !== $county->id, 404);
        abort_if(!$product->is_published || !$product->price, 404);

        $data = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:20',
            'quantity' => 'required|integer|min:1|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $qty = (int) $data['quantity'];
        $total = $product->price * $qty;

        $booking = CountyProductBooking::create([
            'county_product_id' => $product->id,
            'user_id' => $request->user()?->id,
            'customer_name' => $data['customer_name'],
            'customer_email' => $data['customer_email'],
            'customer_phone' => $data['customer_phone'],
            'quantity' => $qty,
            'unit_price' => $product->price,
            'total' => $total,
            'status' => 'pending',
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()
            ->route('county.product.booking.success', [$county->slug, $product->id, $booking->reference])
            ->with('success', 'Booking request received. The seller will contact you for payment and delivery.');
    }

    public function success(County $county, CountyProduct $product, string $reference)
    {
        $booking = CountyProductBooking::where('reference', $reference)
            ->where('county_product_id', $product->id)
            ->firstOrFail();

        return view('experience.pages.counties.product-booking-success', compact('county', 'product', 'booking'));
    }
}
