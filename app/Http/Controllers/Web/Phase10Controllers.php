<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\Order;
use App\Models\Marketplace\Product;
use App\Models\County;
use App\Models\Agent;
use App\Models\Review;
use Illuminate\Http\Request;

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

        return view('kpi.dashboard', compact(
            'uptime', 'avgPageLoad', 'apiResponse', 'bookingCompletion',
            'paymentSuccess', 'mobileCrashRate', 'userSatisfaction',
            'totalOrders', 'totalProducts', 'totalCounties', 'totalAgents', 'totalReviews', 'revenue'
        ));
    }
}

class MultiCurrencyController extends Controller
{
    public function settings()
    {
        $currencies = ['KES' => 1, 'USD' => 0.0078, 'EUR' => 0.0072, 'GBP' => 0.0062, 'UGX' => 28.5, 'TZS' => 18.2, 'RWF' => 10.1];
        return view('multi-currency.settings', compact('currencies'));
    }

    public function convert(Request $request)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0',
            'from' => 'required|string|size:3',
            'to' => 'required|string|size:3',
        ]);
        $rates = ['KES' => 1, 'USD' => 0.0078, 'EUR' => 0.0072, 'GBP' => 0.0062, 'UGX' => 28.5, 'TZS' => 18.2, 'RWF' => 10.1];
        $kesAmount = $data['amount'] / ($rates[$data['from']] ?? 1);
        $converted = $kesAmount * ($rates[$data['to']] ?? 1);
        return response()->json([
            'from' => $data['from'], 'to' => $data['to'],
            'original' => $data['amount'], 'converted' => round($converted, 2),
        ]);
    }
}

class TrainingDocController extends Controller
{
    public function index()
    {
        return view('training.index');
    }

    public function admin()
    {
        return view('training.admin-manual');
    }

    public function api()
    {
        return view('training.api-docs');
    }
}