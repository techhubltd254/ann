<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\FlashSale;
use App\Models\Ecommerce\FlashSaleProduct;
use Illuminate\Http\Request;
class FlashSaleController extends Controller {
    public function index() {
        $active = FlashSale::where('is_active', true)->where('starts_at', '<=', now())->where('ends_at', '>=', now())
            ->with(['products' => fn($q) => $q->with('images')->where('status','active')])->paginate(50);
        $upcoming = FlashSale::where('starts_at', '>', now())->with('products')->latest('starts_at')->paginate(50);
        return view('experience.pages.ecommerce.flash-sales', compact('active','upcoming'));
    }
    public function admin() {
        $sales = FlashSale::with('products')->latest()->paginate(20);
        return view('experience.pages.ecommerce.admin.flash-sales', compact('sales'));
    }
    public function store(Request $r) {
        $data = $r->validate([
            'title'=>'required|string|max:255', 'description'=>'nullable|string',
            'discount_percent'=>'required|numeric|min:0|max:100',
            'starts_at'=>'required|date', 'ends_at'=>'required|date|after:starts_at',
        ]);
        FlashSale::create($data);
        return redirect()->route('kicc-admin.flash-sales')->with('success', 'Flash sale created.');
    }
    public function addProduct(Request $r, $id) {
        $sale = FlashSale::findOrFail($id);
        $data = $r->validate(['product_id'=>'required|exists:products,id','max_qty'=>'required|integer|min:0']);
        $sale->products()->syncWithoutDetaching([$data['product_id'] => ['max_qty'=>$data['max_qty'], 'sold_qty'=>0]]);
        return back()->with('success', 'Product added to flash sale.');
    }
}