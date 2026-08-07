<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\County;
use App\Models\EscrowTransaction;
use App\Models\Marketplace\Order;
use App\Models\Marketplace\Product;
use App\Models\Ministry;
use App\Models\Payment\PaymentIntent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * KICC Overall Admin Portal — the platform owner's god-mode.
 *
 * Controls all four exhibitor tiers: national government, counties,
 * private exhibitors, and the marketplace itself. Every entity links
 * out to its independent public website from here.
 */
class KiccAdminController extends Controller
{
    protected function authorizeKicc(): void
    {
        if (!Auth::user()?->hasRole('kicc_admin')) {
            abort(403, 'KICC admin access required.');
        }
    }

    public function index(Request $request)
    {
        $this->authorizeKicc();
        $tab = $request->get('tab', 'overview');

        $stats = [
            'counties' => County::count(),
            'ministries' => Ministry::count(),
            'agencies' => Agency::count(),
            'exhibitors' => User::where('account_type', 'exhibitor')->count(),
            'tradeBoards' => User::where('email', 'like', 'trade@%.kicc.go.ke')->count(),
            'products' => Product::active()->count(),
            'orders' => Order::count(),
            'users' => User::count(),
            'payments' => PaymentIntent::where('status', 'confirmed')->sum('amount'),
            'escrowHeld' => EscrowTransaction::where('status', 'held')->sum('amount'),
            'escrowTotal' => EscrowTransaction::sum('amount'),
        ];

        // County rollup: trade stats per county — 2 aggregate queries, no N+1
        $productCounts = Product::selectRaw('county_id, COUNT(*) as c')->groupBy('county_id')->pluck('c', 'county_id');
        $tradeVolumes = EscrowTransaction::join('users', 'escrow_transactions.seller_id', '=', 'users.id')
            ->whereNotNull('users.county_id')
            ->selectRaw('users.county_id, SUM(escrow_transactions.amount) as v')
            ->groupBy('users.county_id')->pluck('v', 'county_id');
        $counties = County::orderBy('name')->get()->map(function ($c) use ($productCounts, $tradeVolumes) {
            $c->product_count = $productCounts[$c->id] ?? 0;
            $c->trade_volume = $tradeVolumes[$c->id] ?? 0;
            return $c;
        });

        $exhibitors = User::where('account_type', 'exhibitor')->with('county')->get();
        $exhCounts = Product::selectRaw('user_id, COUNT(*) as c')->whereIn('user_id', $exhibitors->pluck('id'))
            ->groupBy('user_id')->pluck('c', 'user_id');
        $exhibitors->each(fn ($u) => $u->product_count = $exhCounts[$u->id] ?? 0);

        $ministries = Ministry::with('agencies')->orderBy('name')->get();
        $orders = Order::with('items')->latest()->take(50)->get();
        $escrows = EscrowTransaction::with('buyer', 'seller')->latest()->take(50)->get();
        $users = User::with('roles')->latest()->take(50)->get();

        // Provider certification queue (pending services across travel providers)
        $providers = User::where('account_type', 'provider')->get();
        $pendingServices = collect()
            ->merge(\Illuminate\Support\Facades\DB::table('flight_inventory')->where('is_active', 0)->limit(20)->get()->map(fn ($s) => ['table' => 'flight_inventory', 'id' => $s->id, 'label' => 'Flight seat inventory', 'price' => $s->price]))
            ->merge(\Illuminate\Support\Facades\DB::table('hotel_rooms')->where('is_active', 0)->limit(20)->get()->map(fn ($s) => ['table' => 'hotel_rooms', 'id' => $s->id, 'label' => "Room: {$s->name}", 'price' => $s->price_per_night]))
            ->merge(\Illuminate\Support\Facades\DB::table('airport_transfers')->where('is_active', 0)->limit(20)->get()->map(fn ($s) => ['table' => 'airport_transfers', 'id' => $s->id, 'label' => "Transfer: {$s->provider_name} ({$s->vehicle_type})", 'price' => $s->price]))
            ->merge(\Illuminate\Support\Facades\DB::table('flights')->where('status', 'pending')->limit(20)->get()->map(fn ($s) => ['table' => 'flights', 'id' => $s->id, 'label' => "Flight: {$s->flight_number}", 'price' => $s->base_price]));

        $navItems = [
            ['label' => 'Overview', 'tab' => 'overview', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
            ['label' => 'Sub-Portals', 'tab' => 'portals', 'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],
            ['label' => 'Counties (47)', 'tab' => 'counties', 'icon' => 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7'],
            ['label' => 'Counties (47)', 'tab' => 'counties', 'icon' => 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7'],
            ['label' => 'Exhibitors', 'tab' => 'exhibitors', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
            ['label' => 'National Govt', 'tab' => 'national', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
            ['label' => 'Orders', 'tab' => 'orders', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
            ['label' => 'Providers', 'tab' => 'providers', 'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
            ['label' => 'Escrow', 'tab' => 'escrow', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1'],
            ['label' => 'Users', 'tab' => 'users', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197'],
        ];

        return view('dashboards.kicc', compact(
            'tab', 'navItems', 'stats', 'counties', 'exhibitors',
            'ministries', 'orders', 'escrows', 'users', 'providers', 'pendingServices'
        ));
    }

    /** KICC releases escrow funds to a seller after delivery confirmation. */
    public function releaseEscrow(int $id)
    {
        $this->authorizeKicc();
        $escrow = EscrowTransaction::findOrFail($id);
        $steps = collect($escrow->steps ?? [])->map(fn ($s) => array_merge($s, ['done' => true]))->values()->all();
        $escrow->update(['status' => 'released', 'steps' => $steps, 'current_step' => 4, 'released_at' => now()]);
        return redirect()->route('kicc.admin', ['tab' => 'escrow'])->with('success', "Escrow {$escrow->escrow_id} released to {$escrow->seller?->name}.");
    }

    /** KICC certifies a provider's service/price change (govt certification). */
    public function approveService(string $table, int $id)
    {
        $this->authorizeKicc();
        $allowed = ['flight_inventory', 'hotel_rooms', 'airport_transfers', 'flights'];
        abort_unless(in_array($table, $allowed), 404);

        $update = ['is_active' => 1, 'updated_at' => now()];
        if ($table === 'flights') $update = ['status' => 'active', 'updated_at' => now()];
        \Illuminate\Support\Facades\DB::table($table)->where('id', $id)->update($update);

        \App\Services\N8nService::fire('provider_service_approved', ['table' => $table, 'id' => $id]);
        return redirect()->route('kicc.admin', ['tab' => 'providers'])->with('success', 'Service certified and now live.');
    }
}
