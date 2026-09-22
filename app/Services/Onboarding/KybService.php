<?php

namespace App\Services\Onboarding;

/**
 * KYB Tier Registry — Algorithm 13 from kicc-algorithms reference.
 * T0: phone-verified, T1: KRA-PIN validated, T2: asset-verified.
 */
class KybService
{
    public function evaluate(
        bool $phoneVerified,
        ?string $kraPin = null,
        bool $pinValidated = false,
        bool $assetVerified = false
    ): array {
        if (!$phoneVerified) {
            return ['tier' => 0, 'approved' => true, 'reason' => 'phone-verified only'];
        }

        $pinIsValid = $kraPin !== null && preg_match('/^[A-Z][0-9]{9}[A-Z]$|^[A-Z]{2}[0-9]{7}[A-Z]$/', $kraPin);

        if ($assetVerified && $pinValidated && $pinIsValid) {
            return ['tier' => 2, 'approved' => true, 'reason' => 'asset + validated PIN'];
        }
        if ($pinValidated && $pinIsValid) {
            return ['tier' => 1, 'approved' => true, 'reason' => 'validated KRA PIN'];
        }
        if ($kraPin !== null && !$pinIsValid) {
            return ['tier' => 0, 'approved' => false, 'reason' => 'KRA PIN format invalid'];
        }
        return ['tier' => 0, 'approved' => false, 'reason' => 'PIN validation pending'];
    }
}