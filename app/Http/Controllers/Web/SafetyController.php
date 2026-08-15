<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SafetyController extends Controller
{
    public function alerts()
    {
        $alerts = \App\Models\SafetyAlert::with('county')->where('is_active', true)->where(function ($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        })->latest()->get();
        return view('safety.alerts', compact('alerts'));
    }

    public function reportForm()
    {
        $counties = \App\Models\County::orderBy('name')->get();
        return view('safety.report', compact('counties'));
    }

    public function submitReport(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|string|max:50',
            'description' => 'required|string|max:2000',
            'county_id' => 'nullable|exists:counties,id',
            'location' => 'nullable|string|max:255',
        ]);
        \App\Models\IncidentReport::create($data + ['user_id' => Auth::id()]);
        return redirect()->route('safety.alerts')->with('success', 'Report submitted. Authorities have been notified.');
    }
}
