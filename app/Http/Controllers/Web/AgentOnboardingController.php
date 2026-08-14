<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentDocument;
use App\Models\County;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AgentOnboardingController extends Controller
{
    public function register()
    {
        $counties = County::orderBy('name')->get();
        return view('agents.register', compact('counties'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'business_name' => 'required|string|max:255',
            'registration_number' => 'nullable|string|max:255|unique:agents,registration_number',
            'license_number' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:255',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => 'required|string|max:20',
            'website' => 'nullable|url|max:255',
            'address' => 'nullable|string|max:500',
            'description' => 'nullable|string|max:2000',
            'county_id' => 'nullable|exists:counties,id',
            'agent_type' => 'required|in:local,international',
            'service_types' => 'required|array|min:1',
            'service_types.*' => 'string|in:tour_operator,accommodation,transport,guide,restaurant,events',
            'documents' => 'nullable|array',
            'documents.*' => 'file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $agent = Agent::create([
            'user_id' => Auth::id() ?? 0,
            'county_id' => $data['county_id'] ?? null,
            'business_name' => $data['business_name'],
            'registration_number' => $data['registration_number'],
            'license_number' => $data['license_number'],
            'tax_id' => $data['tax_id'],
            'contact_email' => $data['contact_email'],
            'contact_phone' => $data['contact_phone'],
            'website' => $data['website'] ?? null,
            'address' => $data['address'] ?? null,
            'description' => $data['description'] ?? null,
            'agent_type' => $data['agent_type'],
            'service_types' => $data['service_types'],
            'status' => 'pending',
        ]);

        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                $path = $file->store("agents/{$agent->id}/documents", 'public');
                AgentDocument::create([
                    'agent_id' => $agent->id,
                    'document_type' => $this->inferDocumentType($file->getClientOriginalName()),
                    'file_path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'status' => 'pending',
                ]);
            }
        }

        \App\Services\N8nService::fire('agent_onboarded', [
            'agent_id' => $agent->id,
            'business_name' => $agent->business_name,
            'service_types' => $agent->service_types,
        ]);

        return redirect()->route('agent.onboarding.success', $agent->id)
            ->with('success', 'Application submitted. We will review and notify you.');
    }

    public function success(Agent $agent)
    {
        return view('agents.success', compact('agent'));
    }

    protected function inferDocumentType(string $filename): string
    {
        $filename = strtolower($filename);
        if (str_contains($filename, 'license') || str_contains($filename, 'licence')) return 'business_license';
        if (str_contains($filename, 'tax') || str_contains($filename, 'kra') || str_contains($filename, 'pin')) return 'tax_registration';
        if (str_contains($filename, 'certif') || str_contains($filename, 'cert')) return 'certification';
        if (str_contains($filename, 'insurance')) return 'insurance';
        return 'kyc';
    }
}