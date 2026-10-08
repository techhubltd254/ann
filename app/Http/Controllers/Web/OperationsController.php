<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Advertising\Campaign;
use App\Models\Logistics\CourierPartner;
use App\Models\Logistics\ShippingZone;
use App\Models\Seo\ContentPage;
use App\Services\AutomationTreeService;
use Illuminate\Support\Facades\Log;

class OperationsController extends Controller
{
    public function index(AutomationTreeService $tree)
    {
        abort_if(!auth()->check() || !auth()->user()->is_admin, 403);

        try {
            $campaigns = Campaign::where('is_active', true)->get();
        } catch (\Throwable $e) {
            Log::warning('Operations: Campaign query failed: ' . $e->getMessage());
            $campaigns = collect();
        }
        try {
            $couriers = CourierPartner::where('is_active', true)->get();
        } catch (\Throwable $e) {
            Log::warning('Operations: Courier query failed: ' . $e->getMessage());
            $couriers = collect();
        }
        try {
            $zones = ShippingZone::where('is_active', true)->get();
        } catch (\Throwable $e) {
            Log::warning('Operations: ShippingZone query failed: ' . $e->getMessage());
            $zones = collect();
        }
        try {
            $pages = ContentPage::where('is_published', true)->get();
        } catch (\Throwable $e) {
            Log::warning('Operations: ContentPage query failed: ' . $e->getMessage());
            $pages = collect();
        }

        return view('experience.pages.operations.index', compact('campaigns', 'couriers', 'zones', 'pages') + [
            'automationTree' => $tree->getTree(),
        ]);
    }
}
