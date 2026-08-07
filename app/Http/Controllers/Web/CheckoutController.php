<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\EscrowTransaction;
use App\Models\Marketplace\Order;
use App\Models\Marketplace\ShoppingCart;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    protected PaymentService $payments;

    public function __construct(PaymentService $payments)
    {
        $this->payments = $payments;
    }

    protected function cart(Request $request): ?ShoppingCart
    {
        $userId = $request->user()?->id;
        return ShoppingCart::with('items.variant.product.county')
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when(!$userId, fn ($q) => $q->where('session_id', $request->session()->getId()))
            ->latest('id')
            ->first();
    }

    public function index(Request $request)
    {
        $cart = $this->cart($request);
        if (!$cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }
        return view('checkout.index', compact('cart'));
    }

    public function store(Request $request)
    {
        $cart = $this->cart($request);
        if (!$cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:120',
            'county' => 'required|string|max:60',
            'town' => 'required|string|max:60',
            'address' => 'required|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        $order = DB::transaction(function () use ($cart, $data, $request) {
            $subtotal = $cart->subtotal;
            $order = Order::create([
                'user_id' => $request->user()?->id,
                'cart_id' => $cart->id,
                'subtotal' => $subtotal,
                'shipping_total' => 0,
                'tax_total' => 0,
                'grand_total' => $subtotal,
                'notes' => $data['notes'] ?? null,
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
            ]);

            foreach ($cart->items as $item) {
                $variant = $item->variant;
                $order->items()->create([
                    'product_id' => $variant->product_id,
                    'variant_id' => $variant->id,
                    'product_name' => $variant->product->name,
                    'variant_name' => $variant->name,
                    'sku' => $variant->sku,
                    'unit_price' => $item->unit_price,
                    'quantity' => $item->quantity,
                    'total' => $item->unit_price * $item->quantity,
                ]);
                $variant->decrement('stock', $item->quantity);
            }

            $cart->items()->delete();
            $cart->delete();

            // Create a unified payment intent via the active gateway (M-Pesa when key is set)
            $this->payments->charge($order, $order->grand_total, [
                'description' => "Order {$order->order_number}",
                'phone' => $data['phone'],
            ]);

            // Trade escrow: hold funds per seller until buyer confirms delivery
            $order->load('items.variant.product');
            $buyerId = $request->user()?->id
                ?? \App\Models\User::where('email', 'guest@kicc.go.ke')->value('id');
            $bySeller = $order->items->groupBy(fn ($item) => $item->variant?->product?->user_id);
            foreach ($bySeller as $sellerId => $items) {
                if (!$sellerId || !$buyerId) continue;
                $amount = $items->sum('total');
                EscrowTransaction::create([
                    'buyer_id' => $buyerId,
                    'seller_id' => $sellerId,
                    'escrow_id' => 'ESC-' . strtoupper(Str::random(10)),
                    'amount' => $amount,
                    'currency' => $order->currency ?? 'KES',
                    'status' => 'held',
                    'reference_type' => 'order',
                    'reference_id' => $order->id,
                    'steps' => [
                        ['step' => 'funds_held', 'label' => 'Buyer payment held in escrow', 'done' => true, 'at' => now()->toIso8601String()],
                        ['step' => 'seller_ship', 'label' => 'Seller ships goods', 'done' => false],
                        ['step' => 'buyer_confirm', 'label' => 'Buyer confirms delivery', 'done' => false],
                        ['step' => 'released', 'label' => 'Funds released to seller', 'done' => false],
                    ],
                    'current_step' => 1,
                ]);
            }

            return $order;
        });

        \App\Services\N8nService::fire('order_created', [
            'order_number' => $order->order_number, 'total' => $order->grand_total,
            'items' => $order->items->count(), 'phone' => $data['phone'],
        ]);

        return redirect()->route('checkout.success', $order->order_number)
            ->with('customer', $data);
    }

    public function success(string $orderNumber)
    {
        $order = Order::with('items', 'paymentIntents')->where('order_number', $orderNumber)->firstOrFail();
        return view('checkout.success', compact('order'));
    }
}
