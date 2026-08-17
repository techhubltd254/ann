<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

/**
 * Government ID / Business verification with submission-portal fallback.
 *
 * Tier 1: Auto-verify via API (when government API keys exist)
 * Tier 2: Document upload + manual review (submission-portal fallback)
 * Tier 3: Unverified (browsing only, no selling)
 */
class VerificationService
{
    // Verification tiers
    const TIER_NONE = 0;       // Browsing only
    const TIER_LIGHT = 1;      // Phone + email (no Gov ID)
    const TIER_ENHANCED = 2;   // Gov ID submitted (pending review)
    const TIER_VERIFIED = 3;   // Fully verified via API or manual review

    /**
     * Attempt real-time KRA PIN verification.
     * Falls back to submission portal if API key is missing.
     */
    public function verifyKraPin(User $user, string $kraPin, ?UploadedFile $document = null): array
    {
        $apiKey = config('services.verification.kra_api_key');

        if ($apiKey) {
            try {
                $response = \Illuminate\Support\Facades\Http::withToken($apiKey)
                    ->post('https://api.kra.go.ke/v1/verify/pin', ['pin' => $kraPin]);
                if ($response->successful() && ($response->json('status') === 'active')) {
                    $user->update(['kra_pin' => $kraPin, 'verification_tier' => self::TIER_ENHANCED]);
                    return ['verified' => true, 'method' => 'api', 'message' => 'KRA PIN verified via API.'];
                }
            } catch (\Throwable $e) {
                Log::error('KRA API verification failed: ' . $e->getMessage());
            }
        }

        // Fallback: document submission
        $path = null;
        if ($document) {
            $path = $document->store('verification-documents/' . $user->id, 'public');
        }

        $user->update([
            'kra_pin' => $kraPin,
            'verification_tier' => self::TIER_ENHANCED,
            'verification_document_path' => $path,
            'verification_status' => 'pending_review',
        ]);

        Log::info('KRA PIN submitted for manual review', ['user_id' => $user->id, 'kra_pin' => $kraPin]);
        return ['verified' => false, 'method' => 'submission', 'message' => 'Document submitted for manual verification. KRA PIN ' . $kraPin . ' pending review.'];
    }

    /**
     * Verify National ID via Huduma/eCitizen API.
     * Falls back to document upload.
     */
    public function verifyNationalId(User $user, string $idNumber, ?UploadedFile $document = null): array
    {
        $apiKey = config('services.verification.huduma_api_key');

        if ($apiKey) {
            try {
                $response = \Illuminate\Support\Facades\Http::withToken($apiKey)
                    ->post('https://api.huduma.go.ke/v1/verify/id', ['id_number' => $idNumber]);
                if ($response->successful() && $response->json('valid')) {
                    $user->update([
                        'id_number' => $idNumber,
                        'verification_tier' => self::TIER_VERIFIED,
                        'id_verified_at' => now(),
                    ]);
                    return ['verified' => true, 'method' => 'api', 'message' => 'National ID verified via Huduma API.'];
                }
            } catch (\Throwable $e) {
                Log::error('Huduma API verification failed: ' . $e->getMessage());
            }
        }

        // Fallback: document upload
        $path = null;
        if ($document) {
            $path = $document->store('verification-documents/' . $user->id, 'public');
        }

        $user->update([
            'id_number' => $idNumber,
            'verification_tier' => self::TIER_ENHANCED,
            'verification_document_path' => $path,
            'verification_status' => 'pending_review',
        ]);

        return ['verified' => false, 'method' => 'submission', 'message' => 'ID document submitted for manual review.'];
    }

    /**
     * Approve a pending verification (admin action).
     */
    public function approveVerification(User $user, int $adminId): User
    {
        $user->update([
            'verification_tier' => self::TIER_VERIFIED,
            'verification_status' => 'approved',
            'verified_at' => now(),
            'verified_by' => $adminId,
        ]);
        Log::info('Verification approved', ['user_id' => $user->id, 'admin_id' => $adminId]);
        return $user->fresh();
    }

    /**
     * Reject a pending verification with reason.
     */
    public function rejectVerification(User $user, string $reason): User
    {
        $user->update([
            'verification_tier' => self::TIER_LIGHT,
            'verification_status' => 'rejected',
            'verification_rejection_reason' => $reason,
        ]);
        return $user->fresh();
    }
}
