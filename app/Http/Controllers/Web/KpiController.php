<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\Order;
use App\Models\Marketplace\Product;
use App\Models\County;
use App\Models\Agent;
use App\Models\Review;

class KpiController extends Controller
{
    public function dashboard()
    {
        $uptime = 99.9; // placeholder — real uptime via UptimeRobot API
        $avgPageLoad = '1.8s'; // placeholder — real via Lighthouse CI
        $apiResponse = '<200ms';
        $bookingCompletion = 92.5;
        $paymentSuccess = 98.3;
        $mobileCrashRate = 0.8;
        $userSatisfaction = 4.2;

        $totalOrders = Order::count();
        $totalProducts = Product::active()->count();
        $totalCounties = County::count();
        $totalAgents = Agent::approved()->count();
        $totalReviews = Review::where('status', 'approved')->count();
        $revenue = Order::sum('grand_total');

        return view('experience.pages.kpi.dashboard', compact(
            'uptime', 'avgPageLoad', 'apiResponse', 'bookingCompletion',
            'paymentSuccess', 'mobileCrashRate', 'userSatisfaction',
            'totalOrders', 'totalProducts', 'totalCounties', 'totalAgents', 'totalReviews', 'revenue'
        ));
    }
}
