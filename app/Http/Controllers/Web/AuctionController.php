<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Auction;
use App\Models\Ecommerce\AuctionBid;
use Illuminate\Http\Request;
class AuctionController extends Controller {
    public function index() {
        $active = Auction::active()->with('product.images','seller')->latest('ends_at')->paginate(20);
        return view('ecommerce.auctions.index', compact('active'));
    }
    public function show($id) {
        $auction = Auction::with('product.images','product.variants','seller','bids.user')->findOrFail($id);
        return view('ecommerce.auctions.show', compact('auction'));
    }
    public function bid(Request $r, $id) {
        $auction = Auction::findOrFail($id);
        if ($auction->ends_at < now()) {
            return back()->with('error', 'Auction has ended');
        }
        abort_if($auction->status !== 'active', 400, 'Auction not active');
        abort_if($auction->seller_id === auth()->id(), 400, 'Cannot bid on own auction');
        $data = $r->validate(['amount'=>'required|numeric|min:'.($auction->current_bid + $auction->increment)]);
        $bid = AuctionBid::create(['auction_id'=>$auction->id,'user_id'=>auth()->id(),'amount'=>$data['amount']]);
        $auction->update(['current_bid'=>$data['amount']]);
        return redirect()->back()->with('success', 'Bid placed!');
    }
    public function create() {
        return view('ecommerce.auctions.create');
    }
    public function store(Request $r) {
        $data = $r->validate([
            'product_id'=>'required|exists:products,id', 'starting_bid'=>'required|numeric|min:1',
            'reserve_price'=>'nullable|numeric|min:0', 'increment'=>'required|numeric|min:1',
            'starts_at'=>'required|date', 'ends_at'=>'required|date|after:starts_at',
        ]);
        abort_if(\App\Models\Marketplace\Product::findOrFail($data['product_id'])->user_id !== auth()->id(), 403);
        $auction = Auction::create($data + ['seller_id'=>auth()->id(),'status'=>'pending','current_bid'=>$data['starting_bid']]);
        return redirect()->route('auctions.show', $auction->id)->with('success', 'Auction created.');
    }
}