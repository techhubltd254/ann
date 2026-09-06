<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\County\FinancialConfig;
use App\Models\County\WalletTransaction;
use App\Models\Subscription\CountyBulkSlotAllocation;
use App\Models\Subscription\CountySubscriptionPlan;
use App\Models\Subscription\CountySubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CountySubscriptionController extends Controller
{
    public function index(?string $slug = null)
    {
        $county = $slug ? County::where('slug', $slug)->firstOrFail() : County::find(Auth::user()?->county_id ?? 1);
        if (!$county) $county = County::first();

        $plan = CountySubscriptionPlan::active()->withCount('subscribers')->get();
        $config = FinancialConfig::firstOrCreate(['county_id' => $county->id]);
        $allocation = CountyBulkSlotAllocation::where('county_id', $county->id)->paginate(50);
        $subscribers = CountySubscriber::with('user', 'plan')->where('county_id', $county->id)->paginate(50);
        $transactions = WalletTransaction::where('county_id', $county->id)->latest('created_at')->limit(20)->get();

        return view('county-subscriptions.index', compact('county', 'plan', 'config', 'allocation', 'subscribers', 'transactions'));
    }
}
