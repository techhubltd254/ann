<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\CountyInstitution;
use App\Models\ExperienceBooking;
use App\Models\Marketplace\CartItem;
use App\Models\Marketplace\Product;
use App\Models\Marketplace\ShoppingCart;
use App\Services\ExperiencePricingService;
use Illuminate\Http\Request;

class ExperienceController extends Controller
{
    public function create(Request $request)
    {
        $data = $request->validate([
            'destination_type' => 'required|string|in:institution,product',
            'destination_id' => 'required|integer',
            'origin_location' => 'nullable|string|max:255',
            'origin_county_id' => 'nullable|integer|exists:counties,id',
            'departure_date' => 'required|date|after_or_equal:today',
            'return_date' => 'required|date|after_or_equal:departure_date',
            'guest_count' => 'required|integer|min:1|max:50',
            'transport_mode' => 'nullable|string|in:road,train,air+rail,mixed',
        ]);

        $user = $request->user();

        $booking = ExperienceBooking::create([
            'user_id' => $user?->id ?? 0,
            'booking_reference' => 'EXP-' . strtoupper(\Illuminate\Support\Str::random(10)),
            'destination_type' => $data['destination_type'] === 'institution' ? CountyInstitution::class : ($data['destination_type'] === 'product' ? Product::class : null),
            'destination_id' => $data['destination_id'],
            'origin_location' => $data['origin_location'],
            'origin_county_id' => $data['origin_county_id'],
            'departure_date' => $data['departure_date'],
            'return_date' => $data['return_date'],
            'guest_count' => $data['guest_count'],
            'transport_mode' => $data['transport_mode'] ?? null,
            'status' => 'pending',
        ]);

        $pricing = app(ExperiencePricingService::class)->calculate($booking);
        $booking->update([
            'pricing_breakdown' => $pricing['breakdown'],
            'subtotal' => $pricing['subtotal'],
            'grand_total' => $pricing['grand_total'],
        ]);

        $cart = $this->currentCart($request);
        $cart->items()->create([
            'itemable_type' => ExperienceBooking::class,
            'itemable_id' => $booking->id,
            'variant_id' => null,
            'quantity' => 1,
            'unit_price' => $pricing['grand_total'],
        ]);

        return response()->json([
            'success' => true,
            'booking_id' => $booking->id,
            'reference' => $booking->booking_reference,
            'total' => $pricing['grand_total'],
        ]);
    }

    public function setTransport(Request $request, ExperienceBooking $booking)
    {
        $data = $request->validate([
            'transport_out' => 'required|array|min:1',
            'transport_out.*.provider' => 'required|string',
            'transport_out.*.name' => 'required|string',
            'transport_out.*.price' => 'required|numeric|min:0',
            'transport_out.*.type_label' => 'nullable|string',
            'transport_back' => 'nullable|array',
            'transport_back.*.provider' => 'required_with:transport_back|string',
            'transport_back.*.name' => 'required_with:transport_back|string',
            'transport_back.*.price' => 'required_with:transport_back|numeric|min:0',
        ]);

        $booking->update([
            'transport_out' => $data['transport_out'],
            'transport_back' => $data['transport_back'] ?? $data['transport_out'],
        ]);

        $pricing = app(ExperiencePricingService::class)->calculate($booking);
        $booking->update([
            'pricing_breakdown' => $pricing['breakdown'],
            'subtotal' => $pricing['subtotal'],
            'grand_total' => $pricing['grand_total'],
        ]);

        $booking->cartItems->each(function ($item) use ($pricing) {
            $item->update(['unit_price' => $pricing['grand_total']]);
        });

        return response()->json([
            'success' => true,
            'breakdown' => $pricing['breakdown'],
            'total' => $pricing['grand_total'],
            'factors' => $pricing['factors'],
        ]);
    }

    public function addAddon(Request $request, ExperienceBooking $booking)
    {
        $data = $request->validate([
            'type' => 'required|string',
            'label' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'qty' => 'nullable|integer|min:1',
        ]);

        $addons = $booking->addons ?? [];
        $addons[] = [
            'type' => $data['type'],
            'label' => $data['label'],
            'price' => $data['price'],
            'qty' => $data['qty'] ?? 1,
        ];
        $booking->update(['addons' => $addons]);

        $pricing = app(ExperiencePricingService::class)->calculate($booking);
        $booking->update([
            'pricing_breakdown' => $pricing['breakdown'],
            'subtotal' => $pricing['subtotal'],
            'grand_total' => $pricing['grand_total'],
        ]);

        return response()->json(['success' => true, 'total' => $pricing['grand_total']]);
    }

    public function confirm(Request $request, ExperienceBooking $booking)
    {
        $booking->update(['status' => 'confirmed']);
        return back()->with('success', 'Experience booking ' . $booking->booking_reference . ' confirmed.');
    }

    public function cancel(Request $request, ExperienceBooking $booking)
    {
        $booking->update(['status' => 'cancelled']);
        $booking->cartItems->each(fn ($i) => $i->delete());
        return back()->with('success', 'Experience booking cancelled.');
    }

    public function removeFromCart(Request $request, ExperienceBooking $booking)
    {
        $booking->cartItems->each(fn ($i) => $i->delete());
        $booking->update(['status' => 'cancelled']);
        return redirect()->route('cart.index')->with('success', 'Experience removed from cart.');
    }

    public function counties(Request $request)
    {
        $counties = County::orderBy('name')->get(['id', 'name', 'slug']);
        return response()->json($counties);
    }

    public function transportOptions(Request $request)
    {
        $countyId = $request->get('county_id');
        $lat = (float) $request->get('lat', 0);
        $lng = (float) $request->get('lng', 0);

        $options = app(\App\Services\TransportIntegrationService::class)->getOptions($lat, $lng, $countyId, 10);

        return response()->json($options);
    }

    protected function currentCart(Request $request): ShoppingCart
    {
        $userId = $request->user()?->id;
        $sessionId = $request->session()->getId();
        $cart = ShoppingCart::with('items')
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when(!$userId, fn ($q) => $q->where('session_id', $sessionId))
            ->latest('id')
            ->first();
        if (!$cart) {
            $cart = ShoppingCart::create([
                'user_id' => $userId,
                'session_id' => $sessionId,
                'expires_at' => now()->addDays(7),
            ]);
        }
        return $cart;
    }
}