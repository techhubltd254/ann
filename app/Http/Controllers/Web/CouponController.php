<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\CouponUsage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CouponController extends Controller
{
    public function apply(Request $request)
    {
        $data = $request->validate(['code' => 'required|string|max:50']);
        $coupon = Coupon::where('code', strtoupper(trim($data['code'])))->first();
        if (!$coupon || !$coupon->isValid()) {
            return back()->withErrors(['coupon' => 'Invalid or expired coupon code.']);
        }
        $cart = app(\App\Http\Controllers\Web\CartController::class)->currentCart($request);
        if (!$cart) return back()->withErrors(['coupon' => 'Your cart is empty.']);
        $subtotal = $cart->subtotal;
        if ($coupon->min_order_amount && $subtotal < $coupon->min_order_amount) {
            return back()->withErrors(['coupon' => "Minimum order KES {$coupon->min_order_amount} required."]);
        }
        // Check per-user limit
        $userUsage = CouponUsage::where('coupon_id', $coupon->id)
            ->where('user_id', Auth::id())->count();
        if ($userUsage >= $coupon->per_user_limit) {
            return back()->withErrors(['coupon' => 'You have already used this coupon.']);
        }
        $discount = $coupon->calculateDiscount($subtotal);
        session(['coupon_id' => $coupon->id, 'coupon_code' => $coupon->code, 'coupon_discount' => $discount]);
        return redirect()->route('cart.index')->with('success', "Coupon {$coupon->code} applied! Discount: KES {$discount}");
    }

    public function remove()
    {
        session()->forget(['coupon_id', 'coupon_code', 'coupon_discount']);
        return redirect()->route('cart.index')->with('success', 'Coupon removed.');
    }

    // Admin
    public function adminIndex()
    {
        $coupons = Coupon::latest()->paginate(25);
        return view('coupons.admin-index', compact('coupons'));
    }

    public function adminStore(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:50|unique:coupons,code',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0.01',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'per_user_limit' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date',
        ]);
        Coupon::create($data + ['code' => strtoupper($data['code'])]);
        return redirect()->route('coupon.admin.index')->with('success', 'Coupon created.');
    }
}