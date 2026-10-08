<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\TradeEnquiry;
use App\Events\GenericDomainEvent;
use Illuminate\Http\Request;

class TradeAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function enquiries(Request $request)
    {
        $query = TradeEnquiry::with('agreement', 'bloc')->latest();
        $status = $request->get('status');
        if ($status) $query->where('status', $status);
        $enquiries = $query->paginate(25);
        return view('experience.pages.trade-agreements.admin-enquiries', compact('enquiries', 'status'));
    }

    public function updateStatus(Request $request, TradeEnquiry $enquiry)
    {
        $data = $request->validate([
            'status' => 'required|in:submitted,reviewing,approved,rejected,shipped',
            'admin_note' => 'nullable|string|max:1000',
        ]);
        $enquiry->update([
            'status' => $data['status'],
            'admin_note' => $data['admin_note'] ?? $enquiry->admin_note,
            'reviewed_at' => now(),
        ]);
        $enquiry->save();

        event(new GenericDomainEvent('export_enquiry_status_changed', [
            'reference' => $enquiry->reference,
            'status' => $data['status'],
        ], n8nEventName: 'export_enquiry_status_changed'));;
        return redirect()->route('trade.admin.enquiries')->with('success', "Enquiry {$enquiry->reference} updated to {$data['status']}.");
    }
}