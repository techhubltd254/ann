<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\EscrowTransaction;
use App\Models\ExperienceBooking;
use App\Models\Marketplace\Order;
use App\Models\Marketplace\ShoppingCart;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        return ShoppingCart::with(['items.variant.product.county', 'items.itemable'])
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
            'payment_method' => 'required|in:mpesa,cod',
        ]);

        $order = DB::transaction(function () use ($cart, $data, $request) {
            $subtotal = $cart->subtotal;
            $discount = 0;
            // Apply gift card
            if ($code = session('gift_card_code')) {
                $card = \App\Models\Ecommerce\GiftCard::where('code', $code)->active()->first();
                if ($card) {
                    $discount = min($card->balance, $subtotal);
                    $card->decrement('balance', $discount);
                    if ($card->balance <= 0) { $card->update(['is_active' => false]); }
                    session()->forget(['gift_card_code','gift_card_balance']);
                }
            }
            $grandTotal = max(0, $subtotal - $discount);
            $order = Order::create([
                'user_id' => $request->user()?->id,
                'cart_id' => $cart->id,
                'subtotal' => $subtotal,
                'discount_total' => $discount,
                'shipping_total' => 0,
                'tax_total' => 0,
                'grand_total' => $grandTotal,
                'payment_method' => $data['payment_method'],
                'notes' => $data['notes'] ?? null,
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
            ]);

            foreach ($cart->items as $item) {
                if ($item->isExperience() && $item->itemable) {
                    $booking = $item->itemable;
                    $order->items()->create([
                        'product_id' => 0,
                        'variant_id' => null,
                        'product_name' => $booking->destination?->name ?? 'Experience Trip',
                        'variant_name' => $booking->booking_reference . ' · ' . ($booking->origin_location ?? '') . ' → ' . ($booking->destination?->name ?? '') . ' · ' . $booking->departure_date?->format('M d') . '–' . $booking->return_date?->format('M d'),
                        'sku' => $booking->booking_reference,
                        'unit_price' => $booking->grand_total,
                        'quantity' => 1,
                        'total' => $booking->grand_total,
                    ]);
                    $booking->update(['status' => 'confirmed']);
                } else {
                    $variant = $item->variant;
                    if (!$variant) continue;
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
            }

            $cart->items()->delete();
            $cart->delete();

            // Payment (skip for COD)
            if ($data['payment_method'] !== 'cod') {
                $this->payments->charge($order, $order->grand_total, [
                    'description' => "Order {$order->order_number}",
                    'phone' => $data['phone'],
                ]);
            }

            // Trade escrow: hold funds per seller until buyer confirms delivery
            $order->load('items.variant.product');
            $buyerId = $request->user()?->id
                ?? \App\Models\User::where('email', 'guest@kicc.go.ke')->value('id');
            $bySeller = $order->items->groupBy(fn ($item) => $item->variant?->product?->user_id);

            // Determine which pipeline processes this order (default: A1 = marketplace)
            $pipelineCode = $order->pipeline_code ?? 'A1';

            foreach ($bySeller as $sellerId => $items) {
                if (!$sellerId || !$buyerId) continue;
                $amount = $items->sum('total');

                // EscrowService handles the full lifecycle: hold → release → pool accrue → ledger post
                $escrow = app(\App\Services\EscrowService::class)->createEscrow(
                    $buyerId, $sellerId, $amount, $pipelineCode, $order->id
                );
                app(\App\Services\EscrowService::class)->holdFunds($escrow);
                app(\App\Services\EscrowService::class)->confirmBySeller($escrow);
                app(\App\Services\EscrowService::class)->confirmByBuyer($escrow);
                app(\App\Services\EscrowService::class)->releaseFunds($escrow);

                // Record marketplace commission for this seller's items
                foreach ($items as $item) {
                    $agent = \App\Models\Agent::where('user_id', $sellerId)->first();
                    if ($agent && $agent->commission_rate > 0) {
                        $commissionAmount = ($item->total ?? $item->unit_price * $item->quantity) * ($agent->commission_rate / 100);
                        \App\Models\CommissionLog::create([
                            'agent_id' => $agent->id,
                            'order_id' => $order->id,
                            'order_item_id' => $item->id,
                            'item_total' => $item->total ?? $item->unit_price * $item->quantity,
                            'commission_rate' => $agent->commission_rate,
                            'commission_amount' => round($commissionAmount, 2),
                            'commission_type' => 'marketplace',
                            'status' => 'pending',
                        ]);
                    }
                }
            }

            // Record the pipeline that processed this order
            $order->update(['pipeline_code' => $pipelineCode]);

            return $order;
        });

        // Record order status history
        \App\Models\Ecommerce\OrderStatusHistory::create([
            'order_id' => $order->id,
            'status_from' => null,
            'status_to' => 'pending',
            'notes' => 'Order placed via ' . ($data['payment_method'] ?? 'mpesa'),
            'changed_by_user_id' => $request->user()?->id,
        ]);

        \App\Services\N8nService::fire('order_created', [
            'order_number' => $order->order_number, 'total' => $order->grand_total,
            'items' => $order->items->count(), 'phone' => $data['phone'],
        ]);

        // Fire fulfillment and invoice signals for n8n to process
        \App\Services\N8nService::fire('fulfillment_initiated', [
            'order_number' => $order->order_number,
            'payment_method' => $data['payment_method'],
            'shipping_address' => "{$data['address']}, {$data['town']}, {$data['county']}",
            'items' => $order->items->map(fn($i) => [
                'product' => $i->product_name, 'variant' => $i->variant_name,
                'qty' => $i->quantity, 'price' => $i->unit_price,
            ])->toArray(),
        ]);
        \App\Services\N8nService::fire('invoice_generated', [
            'order_number' => $order->order_number,
            'customer_email' => $data['email'] ?? $request->user()?->email,
            'total' => $order->grand_total,
        ]);

        return redirect()->route('checkout.success', $order->order_number)
            ->with('customer', $data);
    }

    public function success(string $orderNumber)
    {
        $order = Order::with('items', 'paymentIntents')->where('order_number', $orderNumber)->firstOrFail();
        // Activate gift cards if this was a gift card purchase
        if (str_contains($order->notes ?? '', 'Gift card purchase')) {
            try {
                $gc = new GiftCardController(app(\App\Services\PaymentService::class));
                $gc->activateByOrder($order);
            } catch (\Throwable $e) {
                Log::warning('activate gift card: ' . $e->getMessage());
            }
        }
        return view('checkout.success', compact('order'));
    }

    public function mpesaCallback(Request $r)
    {
        $allowedIps = ['196.201.214.0/24', '196.201.213.0/24', '196.201.215.0/24', '196.201.216.0/24',
                       '197.136.0.0/14', '212.49.96.0/19', '196.200.0.0/15', '193.218.128.0/19'];
        $clientIp = $r->ip();
        $allowed = false;
        foreach ($allowedIps as $cidr) {
            $parts = explode('/', $cidr);
            $ip = ip2long($clientIp);
            $net = ip2long($parts[0]);
            $mask = -1 << (32 - (int)$parts[1]);
            if (($ip & $mask) === ($net & $mask)) { $allowed = true; break; }
        }
        if (!$allowed && !app()->environment('local')) {
            Log::warning('M-Pesa callback from untrusted IP', ['ip' => $clientIp]);
            return response()->json(['ResultCode' => 1, 'ResultDesc' => 'Forbidden'], 403);
        }

        $payload = $r->all();
        Log::info('M-Pesa callback received', ['payload' => $payload]);

        $phone = $payload['phone'] ?? $payload['Body']['stkCallback']['CallbackMetadata']['Item'][0]['Value'] ?? null;
        $amount = $payload['amount'] ?? $payload['Body']['stkCallback']['CallbackMetadata']['Item'][1]['Value'] ?? null;
        $transactionCode = $payload['TransID'] ?? $payload['Body']['stkCallback']['CallbackMetadata']['Item'][3]['Value'] ?? null;

        $order = Order::where('payment_method', 'mpesa')
            ->where('payment_status', 'pending')
            ->where('grand_total', $amount)
            ->latest()
            ->first();

        if (!$order) {
            Log::warning('M-Pesa callback: order not found', ['phone' => $phone, 'amount' => $amount]);
            return response()->json(['ResultCode' => 1, 'ResultDesc' => 'Order not found']);
        }

        $order->update([
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);

        \App\Models\Ecommerce\OrderStatusHistory::create([
            'order_id' => $order->id,
            'status_from' => 'pending',
            'status_to' => 'paid',
            'notes' => 'Payment confirmed via M-Pesa callback. Transaction: ' . ($transactionCode ?? 'N/A'),
            'changed_by_user_id' => null,
        ]);

        Log::info('M-Pesa callback: order paid', ['order_id' => $order->id, 'transaction' => $transactionCode]);

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Success']);
    }
}
