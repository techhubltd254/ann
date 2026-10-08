<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductCategory;
use App\Models\Marketplace\ProductVariant;
use App\Models\Marketplace\ProductImage;
use App\Models\Marketplace\Order;
use App\Models\Marketplace\OrderItem;
use App\Models\Marketplace\Supplier;
use App\Models\Ecommerce\FlashSale;
use App\Models\Ecommerce\FlashSaleProduct;
use App\Models\Ecommerce\GiftCard;
use App\Models\Ecommerce\Auction;
use App\Models\Ecommerce\Rfq;
use App\Models\User;
use App\Services\Ecommerce\ProductImportService;
use App\Events\GenericDomainEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EcommerceAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin:kicc,national');
    }

    // ─── Dashboard ───
    public function dashboard()
    {
        $stats = [
            'products_total' => Product::count(),
            'products_active' => Product::active()->count(),
            'orders_total' => Order::count(),
            'orders_pending' => Order::where('payment_status', 'pending')->count(),
            'orders_processing' => Order::where('payment_status', 'paid')
                ->where('fulfillment_status', '!=', 'delivered')->count(),
            'orders_delivered' => Order::where('fulfillment_status', 'delivered')->count(),
            'revenue_total' => Order::sum('total'),
            'revenue_month' => Order::whereMonth('created_at', now()->month)->sum('total'),
            'suppliers' => Supplier::count(),
            'counties_with_products' => Product::distinct('county_id')->count('county_id'),
            'categories' => ProductCategory::count(),
            'low_stock' => ProductVariant::where('stock', '<', 10)->where('is_active', true)->count(),
            'flash_sales_active' => FlashSale::where('is_active', true)
                ->where('starts_at', '<=', now())->where('ends_at', '>=', now())->count(),
            'auctions_active' => Auction::active()->count(),
            'gift_cards_active' => GiftCard::active()->count(),
            'rfqs_open' => Rfq::where('status', 'open')->count(),
        ];

        $recentOrders = Order::with('user')->latest()->take(10)->get();
        $topProducts = Product::withCount(['variants' => fn ($q) => $q->select(DB::raw('COALESCE(SUM(stock),0)'))])
            ->active()->latest()->take(5)->get();
        $salesByCounty = Product::select('county_id', DB::raw('COUNT(*) as total'))
            ->groupBy('county_id')->orderByDesc('total')->take(10)->with('county')->get();
        $monthlyRevenue = Order::select(
            DB::raw('YEAR(created_at) as year'), DB::raw('MONTH(created_at) as month'),
            DB::raw('SUM(total) as revenue'), DB::raw('COUNT(*) as orders')
        )->groupBy('year', 'month')->orderByDesc('year')->orderByDesc('month')->take(12)->get();

        return view('experience.pages.admin.ecommerce.dashboard', compact(
            'stats', 'recentOrders', 'topProducts', 'salesByCounty', 'monthlyRevenue'
        ));
    }

    // ─── Products ───
    public function products(Request $request)
    {
        $query = Product::with(['county', 'category', 'variants']);
        if ($search = $request->get('q')) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%"));
        }
        if ($county = $request->get('county')) $query->whereHas('county', fn ($q) => $q->where('slug', $county));
        if ($category = $request->get('category')) $query->where('category_id', $category);
        if (($status = $request->get('status')) && $status !== 'all') $query->where('status', $status);

        return view('experience.pages.admin.ecommerce.products', [
            'products' => $query->latest()->paginate(24),
            'categories' => ProductCategory::active()->get(),
            'counties' => County::orderBy('name')->get(),
            'filters' => $request->only(['q', 'county', 'category', 'status']),
        ]);
    }

    public function productEdit($id = null)
    {
        $product = $id ? Product::with(['variants', 'images', 'county', 'category'])->findOrFail($id) : new Product();
        return view('experience.pages.admin.ecommerce.product-form', [
            'product' => $product,
            'categories' => ProductCategory::active()->get(),
            'counties' => County::orderBy('name')->get(),
        ]);
    }

    public function productUpdate(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:255',
            'county_id' => 'required|exists:counties,id',
            'category_id' => 'required|exists:product_categories,id',
            'status' => 'required|in:draft,active,archived',
            'price' => 'nullable|numeric|min:0',
            'compare_at_price' => 'nullable|numeric|min:0',
            'sku' => 'nullable|string|max:50',
            'unit' => 'nullable|string|max:50',
            'is_featured' => 'boolean',
            'tags' => 'nullable|string',
            'video_url' => 'nullable|url',
            'model_url' => 'nullable|url',
            'weight_kg' => 'nullable|numeric|min:0',
            'moq' => 'nullable|integer|min:1',
        ]);
        $data['tags'] = $data['tags'] ? explode(',', $data['tags']) : [];
        $product->update($data);
        return redirect()->route('admin.ecommerce.products')->with('success', 'Product updated');
    }

    public function productStore(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'county_id' => 'required|exists:counties,id',
            'category_id' => 'required|exists:product_categories,id',
            'status' => 'required|in:draft,active',
            'price' => 'nullable|numeric|min:0',
            'sku' => 'nullable|string|max:50',
        ]);
        $data['slug'] = Str::slug($data['name']) . '-' . strtolower(Str::random(4));
        $data['user_id'] = Auth::id();
        $product = Product::create($data);
        if ($request->price) {
            $product->variants()->create([
                'name' => 'Standard', 'sku' => $product->sku ?? 'KICC-' . strtoupper(Str::random(6)),
                'price' => $request->price, 'stock' => 100, 'is_active' => true,
            ]);
        }
        return redirect()->route('admin.ecommerce.products')->with('success', 'Product created');
    }

    public function productDelete($id)
    {
        Product::findOrFail($id)->delete();
        return back()->with('success', 'Product deleted');
    }

    public function productBulkAction(Request $request)
    {
        $action = $request->input('action');
        $ids = $request->input('ids', []);
        if (empty($ids) || !$action) return back()->with('error', 'Select products and action');

        $count = match ($action) {
            'activate' => Product::whereIn('id', $ids)->update(['status' => 'active']),
            'draft' => Product::whereIn('id', $ids)->update(['status' => 'draft']),
            'archive' => Product::whereIn('id', $ids)->update(['status' => 'archived']),
            'delete' => Product::whereIn('id', $ids)->delete(),
            'feature' => Product::whereIn('id', $ids)->update(['is_featured' => true]),
            'unfeature' => Product::whereIn('id', $ids)->update(['is_featured' => false]),
            default => 0,
        };
        return back()->with('success', "{$count} products updated");
    }

    // ─── Import ───
    public function importForm()
    {
        return view('experience.pages.admin.ecommerce.import', [
            'sources' => ['amazon', 'ebay', 'kilimall', 'jumia', 'kicc'],
            'categories' => ProductCategory::active()->get(),
            'counties' => County::orderBy('name')->get(),
        ]);
    }

    public function importRun(Request $request)
    {
        $request->validate([
            'source' => 'required|in:amazon,ebay,kilimall,jumia,kicc',
            'query' => 'required|string|max:255',
            'limit' => 'integer|min:1|max:50',
        ]);

        $service = app(ProductImportService::class);
        $products = $service->importFromSource(
            $request->source,
            $request->query,
            $request->limit ?? 10
        );

        return redirect()->route('admin.ecommerce.products')
            ->with('success', "Imported " . count($products) . " products from {$request->source}");
    }

    public function importBulk(Request $request)
    {
        $request->validate(['products_json' => 'required|json']);
        $data = json_decode($request->products_json, true);
        $service = app(ProductImportService::class);
        $products = $service->bulkImportFromArray($data);
        return redirect()->route('admin.ecommerce.products')
            ->with('success', "Imported " . count($products) . " products");
    }

    // ─── Variants ───
    public function variantStore(Request $request, $productId)
    {
        $product = Product::findOrFail($productId);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'compare_at_price' => 'nullable|numeric',
            'stock' => 'required|integer|min:0',
            'sku' => 'nullable|string|max:50',
            'image_url' => 'nullable|url',
            'is_active' => 'boolean',
        ]);
        $data['sku'] = $data['sku'] ?? $product->sku . '-V' . ($product->variants()->count() + 1);
        $product->variants()->create($data);
        return back()->with('success', 'Variant added');
    }

    public function variantUpdate(Request $request, $variantId)
    {
        $variant = ProductVariant::findOrFail($variantId);
        $variant->update($request->validate([
            'name' => 'string|max:255', 'price' => 'numeric|min:0',
            'stock' => 'integer|min:0', 'is_active' => 'boolean',
        ]));
        return back()->with('success', 'Variant updated');
    }

    // ─── Orders ───
    public function orders(Request $request)
    {
        $query = Order::with(['user', 'items.product', 'items.variant']);
        if ($search = $request->get('q')) {
            $query->where(fn ($q) => $q->where('order_number', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")));
        }
        if ($status = $request->get('status')) {
            if ($status === 'pending_payment') $query->where('payment_status', 'pending');
            elseif ($status === 'paid') $query->where('payment_status', 'paid')
                ->where('fulfillment_status', '!=', 'delivered');
            elseif ($status === 'delivered') $query->where('fulfillment_status', 'delivered');
            elseif ($status === 'cancelled') $query->where('fulfillment_status', 'cancelled');
        }
        return view('experience.pages.admin.ecommerce.orders', ['orders' => $query->latest()->paginate(20)]);
    }

    public function orderShow($id)
    {
        $order = Order::with(['user', 'items.product', 'items.variant', 'items.supplier',
            'statusHistory', 'paymentIntents'])->findOrFail($id);
        return view('experience.pages.admin.ecommerce.order-detail', compact('order'));
    }

    public function orderUpdateStatus(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $request->validate([
            'payment_status' => 'nullable|in:pending,paid,failed,refunded',
            'fulfillment_status' => 'nullable|in:unfulfilled,processing,shipped,delivered,cancelled',
            'tracking_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request, $order) {
            $changes = [];
            if ($request->payment_status && $request->payment_status !== $order->payment_status) {
                $order->payment_status = $request->payment_status;
                if ($request->payment_status === 'paid') $order->paid_at = now();
                $changes[] = "payment: {$order->payment_status} → {$request->payment_status}";
            }
            if ($request->fulfillment_status && $request->fulfillment_status !== $order->fulfillment_status) {
                $from = $order->fulfillment_status;
                $order->fulfillment_status = $request->fulfillment_status;
                if ($request->fulfillment_status === 'delivered') $order->fulfilled_at = now();
                if ($request->fulfillment_status === 'cancelled') $order->cancelled_at = now();
                $changes[] = "fulfillment: {$from} → {$request->fulfillment_status}";
            }
            if ($request->tracking_number) {
                $order->tracking_number = $request->tracking_number;
                $changes[] = "tracking: {$request->tracking_number}";
            }
            $order->save();

            // Log status history
            $order->statusHistory()->create([
                'status_from' => $changes ? explode(' → ', $changes[0] ?? '')[0] ?? null : null,
                'status_to' => $request->fulfillment_status ?? $request->payment_status ?? 'updated',
                'notes' => $request->notes ?? implode('; ', $changes),
                'changed_by_user_id' => Auth::id(),
            ]);

            // Fire n8n webhook for order update
            try {
                event(new GenericDomainEvent('order_updated', [
                    'order_id' => $order->id, 'order_number' => $order->order_number,
                    'changes' => $changes, 'notes' => $request->notes,
                ], n8nEventName: 'order_updated'));;
            } catch (\Throwable $e) { Log::warning('n8n order update: ' . $e->getMessage()); }
        });

        return back()->with('success', "Order {$order->order_number} updated");
    }

    // ─── Analytics ───
    public function analytics()
    {
        $period = request('period', 'month');

        $revenue = Order::select(
            DB::raw(($period === 'day' ? 'DATE(created_at)' : ($period === 'week' ? 'WEEK(created_at)' : 'DATE_FORMAT(created_at, "%Y-%m")')) . ' as label'),
            DB::raw('SUM(total) as revenue'), DB::raw('COUNT(*) as orders'),
        )->whereNotNull('paid_at')->groupBy('label')->orderBy('label')->take(30)->get();

        $topProducts = Product::select('products.*', DB::raw('COUNT(order_items.id) as sold_count, SUM(order_items.total) as revenue'))
            ->join('order_items', 'products.id', '=', 'order_items.product_id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.payment_status', 'paid')
            ->groupBy('products.id')->orderByDesc('revenue')->take(20)->get();

        $ordersByCounty = Order::select('counties.name as county', DB::raw('COUNT(*) as orders'), DB::raw('SUM(total) as revenue'))
            ->join('counties', 'orders.county_id', '=', 'counties.id')
            ->groupBy('counties.id', 'counties.name')->orderByDesc('revenue')->get();

        $statusBreakdown = [
            'pending_payment' => Order::where('payment_status', 'pending')->count(),
            'paid' => Order::where('payment_status', 'paid')->where('fulfillment_status', '!=', 'delivered')->count(),
            'shipped' => Order::where('fulfillment_status', 'shipped')->count(),
            'delivered' => Order::where('fulfillment_status', 'delivered')->count(),
            'cancelled' => Order::where('fulfillment_status', 'cancelled')->count(),
        ];

        $dailySales = Order::whereDate('created_at', today())
            ->select(DB::raw('HOUR(created_at) as hour, SUM(total) as revenue, COUNT(*) as orders'))
            ->groupBy('hour')->orderBy('hour')->get();

        return view('experience.pages.admin.ecommerce.analytics', compact(
            'revenue', 'topProducts', 'ordersByCounty', 'statusBreakdown', 'dailySales', 'period'
        ));
    }
}