<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\GiftCard;
use App\Models\Marketplace\Order;
use App\Models\Marketplace\OrderItem;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class GiftCardController extends Controller {
    protected PaymentService $payments;

    public function __construct(PaymentService $payments) {
        $this->payments = $payments;
    }

    public function index() { return view('ecommerce.gift-cards.index'); }

    public function purchase(Request $r) {
        $data = $r->validate(['amount'=>'required|numeric|min:100|max:100000','quantity'=>'integer|min:1|max:10']);
        $qty = $data['quantity'] ?? 1;
        $total = $data['amount'] * $qty;

        // Create a pending order for this gift card purchase
        $order = Order::create([
            'user_id' => auth()->id(),
            'subtotal' => $total,
            'grand_total' => $total,
            'payment_method' => 'mpesa',
            'notes' => 'Gift card purchase: '.$qty.' × KES '.number_format($data['amount']),
            'ip_address' => $r->ip(),
            'user_agent' => substr((string) $r->userAgent(), 0, 255),
        ]);

        // Create cards in pending status
        $cards = [];
        for ($i = 0; $i < $qty; $i++) {
            $code = 'KICC-'.strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            $cards[] = GiftCard::create([
                'code' => $code,
                'initial_balance' => $data['amount'],
                'balance' => $data['amount'],
                'issued_by_user_id' => auth()->id(),
                'is_active' => false,
                'expires_at' => now()->addYear(),
                'order_id' => $order->id,
            ]);
        }

        // Charge via payment gateway
        try {
            $this->payments->charge($order, $total, [
                'description' => "Gift cards: {$order->order_number}",
                'phone' => auth()->user()->phone ?? '',
            ]);
        } catch (\Throwable $e) {
            return back()->with('error', 'Payment failed: '.$e->getMessage());
        }

        return redirect()->route('checkout.success', $order->order_number)
            ->with('success', 'Payment initiated. Gift cards will activate once confirmed.');
    }

    public function activateByOrder(Order $order) {
        GiftCard::where('order_id', $order->id)->where('is_active', false)
            ->update(['is_active' => true]);
    }

    public function redeem(Request $r) {
        $data = $r->validate(['code'=>'required|string|size:13','order_id'=>'nullable|exists:orders,id']);
        $card = GiftCard::where('code', $data['code'])->active()->firstOrFail();
        return response()->json(['balance'=>$card->balance]);
    }

    public function apply(Request $r) {
        $data = $r->validate(['code'=>'required|string|size:13']);
        $card = GiftCard::where('code', $data['code'])->active()->firstOrFail();
        session(['gift_card_code' => $card->code, 'gift_card_balance' => $card->balance]);
        return back()->with('success', 'Gift card applied: KES '.number_format($card->balance));
    }

    public function remove() {
        session()->forget(['gift_card_code','gift_card_balance']);
        app(\App\Services\CacheSyncService::class)->kicc();
        return back()->with('success', 'Gift card removed.');
    }
}