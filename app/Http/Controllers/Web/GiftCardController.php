<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\GiftCard;
use Illuminate\Http\Request;
class GiftCardController extends Controller {
    public function index() { return view('ecommerce.gift-cards.index'); }
    public function purchase(Request $r) {
        $data = $r->validate(['amount'=>'required|numeric|min:100|max:100000','quantity'=>'integer|min:1|max:10']);
        $cards = [];
        for ($i = 0; $i < ($data['quantity'] ?? 1); $i++) {
            $code = 'KICC-'.strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            $cards[] = GiftCard::create([
                'code' => $code, 'initial_balance' => $data['amount'], 'balance' => $data['amount'],
                'issued_by_user_id' => auth()->id(), 'is_active' => true,
                'expires_at' => now()->addYear(),
            ]);
        }
        return view('ecommerce.gift-cards.purchased', compact('cards'));
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
        return back()->with('success', 'Gift card removed.');
    }
}