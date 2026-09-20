<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ConsentForm;
use App\Models\ConsentRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ConsentController extends Controller
{
    /**
     * Display a specific consent form for signing.
     */
    public function show($slug)
    {
        $form = ConsentForm::where('slug', $slug)->where('is_active', true)->firstOrFail();
        return view('consent.show', compact('form'));
    }

    /**
     * Process consent signing — creates a tamper-proof digital record.
     * Complies with: Kenya Data Protection Act 2019 §44 (sensitive data),
     * GDPR Art. 7 (consent), Art. 9 (biometric data), Art. 5 (lawfulness).
     */
    public function sign(Request $request, $slug)
    {
        $form = ConsentForm::where('slug', $slug)->where('is_active', true)->firstOrFail();

        $data = $request->validate([
            'signer_name' => 'required|string|max:255',
            'signer_id_number' => 'required|string|max:50',
            'signer_phone' => 'required|string|max:20',
            'signer_email' => 'required|email|max:255',
            'agree_photo' => 'accepted',
            'agree_video' => 'accepted',
            'agree_publish' => 'accepted',
            'agree_terms' => 'accepted',
            'agree_retention' => 'accepted',
            'agree_third_party' => 'nullable|accepted',
        ]);

        // Build immutable agreement record
        $agreements = [
            'photo_capture' => true,
            'video_capture' => true,
            'online_publishing' => true,
            'terms_accepted' => true,
            'retention_10_years' => true,
            'third_party_sharing' => $request->has('agree_third_party'),
            'signed_at' => now()->toIso8601String(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'consent_form_version' => $form->slug,
            'legal_basis' => 'Consent — Kenya DPA 2019 §30(a), GDPR Art. 6(1)(a)',
            'sensitive_data_notice' => 'Biometric data (photographs, video) — Kenya DPA 2019 §44, GDPR Art. 9',
        ];

        $record = ConsentRecord::create([
            'consent_form_id' => $form->id,
            'signer_name' => $data['signer_name'],
            'signer_id_number' => $data['signer_id_number'],
            'signer_phone' => $data['signer_phone'],
            'signer_email' => $data['signer_email'],
            'agreements' => $agreements,
            'ip_address' => $request->ip(),
            'signed_at' => now(),
        ]);

        $form->increment('signed_count');

        // Fire webhook for audit trail
        try {
            \App\Services\N8nService::fire('consent_signed', [
                'consent_form' => $form->slug,
                'signer_name' => $data['signer_name'],
                'signer_email' => $data['signer_email'],
                'signed_at' => $record->signed_at,
            ]);
        } catch (\Throwable $e) {
            Log::warning('consent webhook: ' . $e->getMessage());
        }

        return redirect()->route('consent.confirmation', [
            'slug' => $form->slug,
            'record' => $record->id,
        ]);
    }

    /**
     * Show confirmation after signing — includes a reference number.
     */
    public function confirmation($slug, $record)
    {
        $form = ConsentForm::where('slug', $slug)->firstOrFail();
        $record = ConsentRecord::findOrFail($record);

        return view('consent.confirmation', compact('form', 'record'));
    }

    /**
     * Verify a consent record (public lookup by reference).
     */
    public function verify(Request $request)
    {
        $request->validate(['reference' => 'required|integer|exists:consent_records,id']);
        $record = ConsentRecord::with('consentForm')->findOrFail($request->reference);
        return view('consent.verify', compact('record'));
    }

    /**
     * Physical/printable consent form — includes QR code linking to digital version.
     */
    public function physical()
    {
        return view('consent.physical');
    }
}