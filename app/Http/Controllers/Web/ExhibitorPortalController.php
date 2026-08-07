<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\EscrowTransaction;
use App\Models\Marketplace\Order;
use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Private Exhibitor Portal — the atomic exhibitor unit.
 *
 * Every private exhibitor gets: a dashboard (stats), product management,
 * order tracking, escrow earnings, and a public storefront website.
 * Replicated pattern for County and National tiers.
 */
class ExhibitorPortalController extends Controller
{
    /** Guard: exhibitor role, or KICC admin (who may enter any portal). */
    protected function authorizeExhibitor(): void
    {
        if (!Auth::user()?->hasAnyRole(['exhibitor', 'kicc_admin'])) {
            abort(403, 'Exhibitor access required.');
        }
    }

    public function index(Request $request)
    {
        $this->authorizeExhibitor();
        $user = Auth::user();

        // First visit? Complete the setup wizard first (unless KICC admin browsing)
        $meta = (array) ($user->metadata ?? []);
        if (empty($meta['onboarding_complete']) && !$user->hasRole('kicc_admin')) {
            return redirect()->route('exhibitor.onboarding');
        }

        $tab = $request->get('tab', 'overview');

        $products = Product::with(['category', 'variants', 'county'])
            ->where('user_id', $user->id)->latest()->get();

        $orders = Order::with(['items' => fn ($q) => $q->whereHas('variant.product', fn ($p) => $p->where('user_id', $user->id))])
            ->whereHas('items.variant.product', fn ($p) => $p->where('user_id', $user->id))
            ->latest()->take(50)->get();

        $escrows = EscrowTransaction::with('buyer')->where('seller_id', $user->id)->latest()->take(50)->get();

        $stats = [
            'products' => $products->count(),
            'stock' => $products->sum(fn ($p) => $p->variants->sum('stock')),
            'orders' => $orders->count(),
            'revenue' => $escrows->sum('amount'),
            'escrowHeld' => $escrows->where('status', 'held')->sum('amount'),
            'escrowReleased' => $escrows->where('status', 'released')->sum('amount'),
        ];

        $navItems = [
            ['label' => 'Overview', 'tab' => 'overview', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
            ['label' => 'My Products', 'tab' => 'products', 'icon' => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z'],
            ['label' => 'Orders', 'tab' => 'orders', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
            ['label' => 'Escrow Earnings', 'tab' => 'escrow', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1'],
            ['label' => 'My Website', 'tab' => 'website', 'icon' => 'M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9'],
        ];

        return view('dashboards.exhibitor', [
            'user' => $user,
            'tab' => $tab,
            'navItems' => $navItems,
            'products' => $products,
            'orders' => $orders,
            'escrows' => $escrows,
            'stats' => $stats,
            'categories' => ProductCategory::where('is_active', true)->orderBy('sort_order')->get(),
            'storefrontUrl' => route('exhibitor.site', Str::slug($user->name)),
        ]);
    }

    public function storeProduct(Request $request)
    {
        $this->authorizeExhibitor();
        $user = Auth::user();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:product_categories,id',
            'description' => 'required|string|max:2000',
            'price' => 'required|numeric|min:1',
            'stock' => 'required|integer|min:0',
            'unit' => 'nullable|string|max:50',
        ]);

        $slug = Str::slug($user->name . ' ' . $data['name']);
        if (Product::where('slug', $slug)->exists()) {
            $slug .= '-' . strtolower(Str::random(4));
        }

        $product = Product::create([
            'user_id' => $user->id,
            'county_id' => $user->county_id,
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'],
            'short_description' => Str::limit($data['description'], 120),
            'sku' => 'KICC-EX-' . strtoupper(Str::random(6)),
            'unit' => $data['unit'] ?? 'unit',
            'status' => 'active',
        ]);

        $countySlug = County::find($user->county_id)?->slug;
        $product->variants()->create([
            'name' => 'Standard ' . ($data['unit'] ?? 'unit'),
            'sku' => $product->sku . '-V1',
            'price' => $data['price'],
            'stock' => $data['stock'],
            'is_active' => true,
            'image_url' => $countySlug ? media("counties/{$countySlug}/products.jpeg") : null,
        ]);

        return redirect()->route('exhibitor.admin', ['tab' => 'products'])->with('success', "Product \"{$product->name}\" is now live on the marketplace.");
    }

    public function deleteProduct(int $id)
    {
        $this->authorizeExhibitor();
        Product::where('user_id', Auth::id())->findOrFail($id)->delete();
        return redirect()->route('exhibitor.admin', ['tab' => 'products'])->with('success', 'Product removed.');
    }
}
