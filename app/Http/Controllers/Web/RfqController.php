<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Rfq;
use App\Models\Ecommerce\RfqQuote;
use Illuminate\Http\Request;
class RfqController extends Controller {
    public function index() {
        $quotes = Rfq::where('buyer_id', auth()->id())->with('quotes.seller')->latest()->paginate(15);
        return view('ecommerce.rfq.index', compact('quotes'));
    }
    public function create() { return view('ecommerce.rfq.create'); }
    public function store(Request $r) {
        $data = $r->validate([
            'product_name'=>'required|string|max:255', 'quantity'=>'required|integer|min:1',
            'specifications'=>'nullable|string|max:5000', 'budget_min'=>'nullable|numeric|min:0',
            'budget_max'=>'nullable|numeric|min:0', 'deadline'=>'nullable|date',
        ]);
        $rfq = Rfq::create($data + ['rfq_number'=>'RFQ-'.strtoupper(substr(uniqid(),-8)), 'buyer_id'=>auth()->id()]);
        return redirect()->route('rfq.index')->with('success', 'RFQ submitted. Sellers will respond soon.');
    }
    public function sellerIndex() {
        $openRfqs = Rfq::where('status','open')->with('buyer')->latest()->paginate(20);
        $myQuotes = RfqQuote::where('seller_id', auth()->id())->with('rfq.buyer')->latest()->paginate(10);
        return view('ecommerce.rfq.seller', compact('openRfqs','myQuotes'));
    }
    public function quote(Request $r, $rfqId) {
        $data = $r->validate(['price'=>'required|numeric|min:0','notes'=>'nullable|string|max:2000']);
        RfqQuote::create($data + ['rfq_id'=>$rfqId, 'seller_id'=>auth()->id()]);
        return back()->with('success', 'Quote submitted.');
    }
}