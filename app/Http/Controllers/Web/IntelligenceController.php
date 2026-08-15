<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\Marketplace\Order;
use App\Models\Marketplace\Product;
use App\Models\CountyHotel;
use App\Models\CountyTourismAttraction;
use App\Models\TradeEnquiry;
use App\Models\Agent;
use App\Models\Review;
use App\Models\CommissionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IntelligenceController extends Controller
{
    public function dashboard(Request $request)
    {
        abort_if(!auth()->check() || !auth()->user()->is_admin, 403);

        $period = $request->get('period', 'month');
        $dateFrom = match($period) {
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'quarter' => now()->subMonths(3),
            'year' => now()->subYear(),
            default => now()->subMonth(),
        };

        // Orders / revenue
        $orders = Order::where('created_at', '>=', $dateFrom);
        $totalOrders = (clone $orders)->count();
        $totalRevenue = (clone $orders)->sum('grand_total');
        $orderGrowth = Order::where('created_at', '>=', now()->subDays(7))->count();

        // Tourism entities
        $totalHotels = CountyHotel::count();
        $totalAttractions = CountyTourismAttraction::count();
        $products = Product::active()->count();
        $agents = Agent::approved()->count();
        $pendingAgents = Agent::pending()->count();

        // Reviews
        $totalReviews = Review::count();
        $avgRating = Review::where('status', 'approved')->avg('rating');

        // Enquiries
        $enquiries = TradeEnquiry::count();
        $pendingEnquiries = TradeEnquiry::where('status', 'submitted')->count();

        // Commissions
        $commissions = CommissionLog::sum('commission_amount');
        $pendingCommissions = CommissionLog::where('status', 'pending')->sum('commission_amount');

        // Top counties by products
        $topCounties = County::withCount(['products as products_count' => fn ($q) => $q->where('status', 'active')])
            ->orderByDesc('products_count')->take(10)->get(['name', 'slug']);

        // Daily order trend
        $dailyTrend = Order::where('created_at', '>=', $dateFrom)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'), DB::raw('SUM(grand_total) as revenue'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return view('intelligence.dashboard', compact(
            'period', 'totalOrders', 'totalRevenue', 'orderGrowth',
            'totalHotels', 'totalAttractions', 'products', 'agents', 'pendingAgents',
            'totalReviews', 'avgRating',
            'enquiries', 'pendingEnquiries',
            'commissions', 'pendingCommissions',
            'topCounties', 'dailyTrend'
        ));
    }
}