<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentDocument;
use App\Events\GenericDomainEvent;
use Illuminate\Http\Request;

class AgentAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $query = Agent::with('user', 'county', 'documents')->latest();
        $status = $request->get('status');
        if ($status) $query->where('status', $status);
        $agents = $query->paginate(25);
        return view('agents.admin-index', compact('agents', 'status'));
    }

    public function show(Agent $agent)
    {
        $agent->load('user', 'county', 'documents');
        return view('agents.admin-show', compact('agent'));
    }

    public function approve(Request $request, Agent $agent)
    {
        $data = $request->validate([
            'commission_rate' => 'nullable|numeric|min:0|max:50',
            'notes' => 'nullable|string|max:500',
        ]);
        $agent->update([
            'status' => 'approved',
            'commission_rate' => $data['commission_rate'] ?? $agent->commission_rate,
            'approved_at' => now(),
        ]);
        $agent->documents()->update(['status' => 'verified', 'verified_at' => now()]);

        event(new GenericDomainEvent('agent_approved', [
            'agent_id' => $agent->id,
            'business_name' => $agent->business_name,
        ], n8nEventName: 'agent_approved'));;
        return redirect()->route('agent.admin.index')->with('success', "{$agent->business_name} approved.");
    }

    public function reject(Request $request, Agent $agent)
    {
        $data = $request->validate(['notes' => 'nullable|string|max:500']);
        $agent->update(['status' => 'rejected']);

        event(new GenericDomainEvent('agent_rejected', [
            'agent_id' => $agent->id,
            'business_name' => $agent->business_name,
            'notes' => $data['notes'] ?? '',
        ], n8nEventName: 'agent_rejected'));;
        return redirect()->route('agent.admin.index')->with('success', "{$agent->business_name} rejected.");
    }

    public function verifyDocument(Request $request, AgentDocument $document)
    {
        $document->update(['status' => 'verified', 'verified_at' => now()]);
        return back()->with('success', 'Document verified.');
    }
}