<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\Marketplace\ProductCategory;
use App\Models\TradeAgreement;
use App\Models\TradeEnquiry;
use App\Models\TradingBloc;
use Illuminate\Http\Request;

class TradeExportController extends Controller
{
    public function eligibility()
    {
        $categories = ProductCategory::active()->orderBy('name')->get();
        $blocs = TradingBloc::where('is_active', true)->orderBy('name')->get();
        return view('trade-agreements.eligibility-index', compact('categories', 'blocs'));
    }

    public function checkEligibility(Request $request)
    {
        $data = $request->validate([
            'product_category' => 'nullable|string|max:255',
            'destination' => 'nullable|string|max:255',
            'trading_bloc_id' => 'nullable|exists:trading_blocs,id',
        ]);

        $appliedCategory = $data['product_category'] ?? '';
        $appliedDestination = $data['destination'] ?? '';
        $appliedBloc = $data['trading_bloc_id'] ?? null;

        $query = TradeAgreement::with('bloc')->active();
        if ($appliedCategory) {
            $query->where(function ($q) use ($appliedCategory) {
                $q->where('sector_coverage', 'like', "%{$appliedCategory}%")
                  ->orWhere('summary', 'like', "%{$appliedCategory}%")
                  ->orWhere('title', 'like', "%{$appliedCategory}%");
            });
        }
        if ($appliedBloc) {
            $query->where('trading_bloc_id', $appliedBloc);
        } elseif ($appliedDestination) {
            $query->where(function ($q) use ($appliedDestination) {
                $q->where('partner_country', 'like', "%{$appliedDestination}%")
                  ->orWhere('title', 'like', "%{$appliedDestination}%");
            });
        }
        $matches = $query->latest()->paginate(50);
        $blocs = TradingBloc::where('is_active', true)->orderBy('name')->get();

        return view('trade-agreements.eligibility-results', compact('matches', 'appliedCategory', 'appliedDestination', 'blocs'));    }

    public function applyForm($slug)
    {
        $agreement = TradeAgreement::where('slug', $slug)->firstOrFail();
        $counties = County::orderBy('name')->get();
        return view('trade-agreements.apply', compact('agreement', 'counties'));
    }

    public function storeEnquiry(Request $request)
    {
        $data = $request->validate([
            'trade_agreement_id' => 'nullable|exists:trade_agreements,id',
            'product_name' => 'nullable|string|max:255',
            'product_category' => 'nullable|string|max:255',
            'company_name' => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'destination' => 'nullable|string|max:255',
            'estimated_value' => 'nullable|numeric|min:0',
            'message' => 'nullable|string|max:2000',
        ]);

        $enquiry = TradeEnquiry::create([
            'trade_agreement_id' => $data['trade_agreement_id'],
            'product_name' => $data['product_name'],
            'product_category' => $data['product_category'],
            'company_name' => $data['company_name'],
            'contact_name' => $data['contact_name'],
            'contact_email' => $data['contact_email'],
            'contact_phone' => $data['contact_phone'],
            'destination' => $data['destination'],
            'estimated_value' => $data['estimated_value'],
            'message' => $data['message'],
        ]);

        \App\Services\N8nService::fire('export_enquiry_created', [
            'reference' => $enquiry->reference,
            'company_name' => $enquiry->company_name,
            'product_name' => $enquiry->product_name,
            'destination' => $enquiry->destination,
            'estimated_value' => $enquiry->estimated_value,
        ]);

        return redirect()->route('trade.enquiry.success', $enquiry->reference)
            ->with('success', "Export enquiry {$enquiry->reference} submitted.");
    }

    public function enquirySuccess($reference)
    {
        $enquiry = TradeEnquiry::where('reference', $reference)->firstOrFail();
        return view('trade-agreements.enquiry-success', compact('enquiry'));
    }
}