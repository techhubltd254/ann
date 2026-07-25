<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\County\FinancialConfig;
use App\Models\County\WalletTransaction;
use App\Models\Exhibition;
use App\Models\Marketplace\Order;
use App\Models\Marketplace\Product;
use App\Models\Payment\PaymentIntent;
use App\Models\Subscription\CountySubscriber;
use App\Models\User;
use App\Models\Venue;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'counties' => County::count(),
            'users' => User::count(),
            'products' => Product::active()->count(),
            'orders' => Order::count(),
            'exhibitions' => Exhibition::where('status', 'published')->count(),
            'venues' => Venue::where('is_active', true)->count(),
            'paymentVolume' => PaymentIntent::where('status', 'confirmed')->sum('amount'),
            'activeSubs' => CountySubscriber::where('status', 'active')->count(),
        ];

        $recentOrders = Order::with('items')->latest()->take(5)->get();
        $recentPayments = PaymentIntent::latest()->take(5)->get();
        $counties = County::orderBy('name')->get(['id', 'name', 'slug']);

        $navItems = [
            ['label' => 'Overview', 'active' => true, 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
            ['label' => 'Counties', 'icon' => 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7'],
            ['label' => 'Payments', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1'],
            ['label' => 'Users', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197'],
        ];

        return view('dashboards.admin', compact('stats', 'recentOrders', 'recentPayments', 'counties', 'navItems'));
    }
}
