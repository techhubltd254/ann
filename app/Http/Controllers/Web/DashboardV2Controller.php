<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\County\FinancialConfig;
use App\Models\County\WalletTransaction;
use App\Models\Subscription\CountyBulkSlotAllocation;
use App\Models\Subscription\CountySubscriber;
use Illuminate\Support\Facades\Auth;

class DashboardV2Controller extends Controller
{
    public function county()
    {
        $county = County::find(Auth::user()?->county_id ?? 1) ?? County::first();
        $config = FinancialConfig::firstOrCreate(['county_id' => $county->id]);
        $allocation = CountyBulkSlotAllocation::where('county_id', $county->id)->get();
        $subscribers = CountySubscriber::with('user', 'plan')->where('county_id', $county->id)->get();
        $transactions = WalletTransaction::where('county_id', $county->id)->latest('created_at')->limit(20)->get();

        $tab = request('tab', 'overview');
        $navItems = [
            ['label' => 'Overview', 'tab' => 'overview', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
            ['label' => 'Slots', 'tab' => 'slots', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
            ['label' => 'Subscribers', 'tab' => 'subscribers', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
            ['label' => 'Content', 'tab' => 'content', 'icon' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'],
            ['label' => 'Settlements', 'tab' => 'settlements', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
        ];

        return view('dashboards.county', compact('county', 'config', 'allocation', 'subscribers', 'transactions', 'navItems', 'tab'));
    }
}