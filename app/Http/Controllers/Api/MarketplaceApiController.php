<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\Order;
use App\Models\Marketplace\Product;
use App\Models\Ecommerce\FlashSale;
use App\Models\Ecommerce\Auction;
use App\Models\Marketplace\OrderItem;
use App\Models\County;
use App\Models\Marketplace\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MarketplaceApiController extends Controller
{
    // Public endpoints — no auth required
    public function products(Request $request)
    {
        $query = Product::active()->with(['county', 'category', 'variants' => fn ($q) => $q->where('is_active', true), 'images']);
        if ($search = $request->q) $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
            ->orWhere('short_description', 'like', "%{$search}%"));
        if ($county = $request->county) $query->whereHas('county', fn ($q) => $q->where('slug', $county));
        if ($category = $request->category) $query->whereHas('category', fn ($q) => $q->where('slug', $category));
        if ($minPrice = $request->min_price) $query->whereRelation('variants', 'price', '>=', $minPrice);
        if ($maxPrice = $request->max_price) $query->whereRelation('variants', 'price', '<=', $maxPrice);
        if ($featured = $request->featured) $query->where('is_featured', true);
        $sort = $request->sort ?? 'created_at';
        $query->orderByDesc($sort);
        return response()->json($query->take($request->limit ?? 24)->get());
    }

    public function productShow($id)
    {
        $product = Product::with(['county', 'category', 'variants' => fn ($q) => $q->where('is_active', true), 'images'])->findOrFail($id);
        $product->increment('views_count');
        return response()->json($product);
    }

    public function categories()
    {
        return response()->json(ProductCategory::active()->withCount('products')->get());
    }

    public function flashSales()
    {
        return response()->json(
            FlashSale::where('is_active', true)->where('starts_at', '<=', now())
                ->where('ends_at', '>=', now())->with('products')->get()
        );
    }

    public function auctions()
    {
        return response()->json(Auction::active()->with(['product', 'bids' => fn ($q) => $q->latest()->take(5)])->get());
    }

    // Auth-required
    public function myOrders(Request $request)
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->with(['items.product', 'items.variant', 'statusHistory'])
            ->latest()->paginate(15);
        return response()->json($orders);
    }

    public function orderShow(Request $request, $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)
            ->where('user_id', $request->user()->id)
            ->with(['items.product', 'items.variant', 'statusHistory.user', 'paymentIntents'])
            ->firstOrFail();
        return response()->json($order);
    }

    public function orderStats()
    {
        return response()->json([
            'total_products' => Product::count(),
            'active_products' => Product::active()->count(),
            'total_orders' => Order::count(),
            'revenue_month' => Order::whereMonth('created_at', now()->month)->sum('grand_total'),
            'revenue_year' => Order::whereYear('created_at', now()->year)->sum('grand_total'),
            'orders_by_status' => [
                'pending' => Order::where('payment_status', 'pending')->count(),
                'paid' => Order::where('payment_status', 'paid')->count(),
                'delivered' => Order::where('fulfillment_status', 'delivered')->count(),
                'cancelled' => Order::where('fulfillment_status', 'cancelled')->count(),
            ],
            'top_counties' => Product::select('county_id', DB::raw('COUNT(*) as total'))
                ->groupBy('county_id')->orderByDesc('total')->take(5)->with('county')->get(),
        ]);
    }
}