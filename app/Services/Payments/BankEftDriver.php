<?php

namespace App\Services\Payments;

use App\Models\Payment\PaymentIntent;
use Illuminate\Support\Facades\Log;

/**
 * Bank EFT / wire transfer payment driver.
 * Generates payment instructions; payment is confirmed via admin manual verification
 * or webhook from the bank (when available).
 */
class BankEftDriver
{
    public function process(PaymentIntent $intent, array $meta = []): array
    {
        $bankName = config('services.bank_eft.bank_name', 'KICC Designated Account');
        $accountNo = config('services.bank_eft.account_number', '0000000000');
        $branch = config('services.bank_eft.branch', 'Nairobi');

        Log::info('bank_eft: payment instructions generated', ['intent' => $intent->id]);

        return [
            'success' => true,
            'request' => ['amount' => $intent->amount, 'reference' => $intent->intent_id],
            'response' => [
                'message' => 'Bank EFT payment instructions generated.',
                'instructions' => [
                    'bank_name' => $bankName,
                    'account_name' => 'KICC National Exhibition Platform',
                    'account_number' => $accountNo,
                    'branch' => $branch,
                    'reference' => $intent->intent_id,
                    'amount' => $intent->amount,
                    'currency' => $intent->currency ?? 'KES',
                ],
            ],
            'status' => 'pending_manual',
            'transaction_ref' => 'BANK-' . $intent->intent_id,
        ];
    }

    public function verify(string $transactionRef): array
    {
        return [
            'success' => true,
            'status' => 'pending_manual',
            'message' => 'Bank EFT requires manual verification. Check account statement for payment.',
        ];
    }
}