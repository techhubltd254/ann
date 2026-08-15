<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\Order;
use App\Models\Marketplace\Product;
use Illuminate\Http\Request;
class SellerAnalyticsController extends Controller {
    public function dashboard() {
        $userId = auth()->id();
        $products = Product::where('user_id', $userId)->with('variants','images')->get();
        $productIds = $products->pluck('id');
        $orders = Order::whereHas('items', fn($q) => $q->whereIn('product_id', $productIds))->with('items')->latest()->get();
        $totalSales = $orders->sum('grand_total');
        $totalOrders = $orders->count();
        $totalProducts = $products->count();
        $topProducts = $products->sortByDesc(fn($p) => $orders->filter(fn($o) => $o->items->contains('product_id', $p->id))->count())->take(5);
        return view('ecommerce.seller.analytics', compact('products','orders','totalSales','totalOrders','totalProducts','topProducts'));
    }
}