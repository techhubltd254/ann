<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Advertising\Campaign;
use App\Models\Logistics\CourierPartner;
use App\Models\Logistics\ShippingZone;
use App\Models\Seo\ContentPage;
use App\Services\AutomationTreeService;

class OperationsController extends Controller
{
    public function index(AutomationTreeService $tree)
    {
        return view('operations.index', [
            'campaigns' => Campaign::where('is_active', true)->get(),
            'couriers' => CourierPartner::where('is_active', true)->get(),
            'zones' => ShippingZone::where('is_active', true)->get(),
            'pages' => ContentPage::where('is_published', true)->get(),
            'automationTree' => $tree->getTree(),
        ]);
    }
}
