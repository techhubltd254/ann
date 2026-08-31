<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ExperienceBooking;
use App\Models\Marketplace\CartItem;
use App\Models\Marketplace\ProductVariant;
use App\Models\Marketplace\ShoppingCart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function currentCart(Request $request): ShoppingCart
    {
        $userId = $request->user()?->id;
        $sessionId = $request->session()->getId();

        $cart = ShoppingCart::with(['items.variant.product.county', 'items.itemable'])
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

        return $cart->load(['items.variant.product.county', 'items.itemable']);
    }

    public function index(Request $request)
    {
        return view('cart.index', ['cart' => $this->currentCart($request)]);
    }

    public function add(Request $request)
    {
        $data = $request->validate([
            'variant_id' => 'required|exists:product_variants,id',
            'quantity' => 'nullable|integer|min:1|max:99',
        ]);

        $variant = ProductVariant::where('is_active', true)->findOrFail($data['variant_id']);
        $cart = $this->currentCart($request);
        $qty = $data['quantity'] ?? 1;

        $item = $cart->items()->where('variant_id', $variant->id)->whereNull('itemable_type')->first();
        if ($item) {
            $item->increment('quantity', $qty);
        } else {
            $cart->items()->create([
                'variant_id' => $variant->id,
                'quantity' => $qty,
                'unit_price' => $variant->price,
            ]);
        }

        return back()->with('success', 'Added to cart.');
    }

    public function update(Request $request, CartItem $item)
    {
        $cart = $item->cart;
        abort_if($cart->user_id !== auth()->id() && $cart->session_id !== session()->getId(), 403);

        if ($item->isExperience()) {
            return back()->with('info', 'Experience bookings cannot be adjusted — remove and re-book instead.');
        }

        $data = $request->validate(['quantity' => 'required|integer|min:0|max:99']);
        if ($data['quantity'] === 0) {
            $item->delete();
        } else {
            $item->update(['quantity' => $data['quantity']]);
        }
        return back()->with('success', 'Cart updated.');
    }

    public function destroy(CartItem $item)
    {
        $cart = $item->cart;
        abort_if($cart->user_id !== auth()->id() && $cart->session_id !== session()->getId(), 403);

        // If it's an experience item, also cancel the booking
        if ($item->isExperience() && $item->itemable) {
            $item->itemable->update(['status' => 'cancelled']);
        }

        $item->delete();
        return back()->with('success', 'Item removed.');
    }
}
