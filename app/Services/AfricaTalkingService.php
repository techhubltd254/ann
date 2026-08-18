<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AfricaTalkingService
{
    protected ?string $username;
    protected ?string $apiKey;

    public function __construct()
    {
        $this->username = config('services.africastalking.username');
        $this->apiKey = config('services.africastalking.key');
    }

    public function send(string $to, string $message, ?string $from = null): array
    {
        if (! $this->apiKey) {
            Log::info('africas_talking: stub mode (no API key)', ['to' => $to]);
            return ['success' => true, 'stub' => true, 'message' => 'SMS stub — configure AFRICAS_TALKING_API_KEY to send real SMS.'];
        }

        try {
            $response = Http::withHeaders(['apiKey' => $this->apiKey])
                ->asForm()
                ->post('https://api.africastalking.com/version1/messaging', [
                    'username' => $this->username ?? 'sandbox',
                    'to' => $to,
                    'message' => $message,
                    'from' => $from ?? config('services.africas_talking.from', 'KICC'),
                ]);

            $body = $response->json();
            $success = ($body['SMSMessageData']['Recipients'][0]['status'] ?? 'failed') === 'Success';

            return [
                'success' => $success,
                'response' => $body,
                'message_id' => $body['SMSMessageData']['Recipients'][0]['messageId'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::error('AfricaTalking SMS failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function sendOtp(string $phone, string $otp): array
    {
        return $this->send($phone, "Your KICC verification code is: $otp. Valid for 10 minutes.");
    }

    public function fetchUssdHistory(string $phone, int $limit = 10): array
    {
        if (! $this->apiKey) {
            return ['success' => true, 'stub' => true, 'records' => []];
        }

        try {
            $response = Http::withHeaders(['apiKey' => $this->apiKey])
                ->get('https://api.africastalking.com/version1/ussd/history', [
                    'username' => $this->username ?? 'sandbox',
                    'phoneNumber' => $phone,
                    'limit' => $limit,
                ]);

            return ['success' => $response->successful(), 'records' => $response->json()['responses'] ?? []];
        } catch (\Throwable $e) {
            Log::error('AfricaTalking USSD history failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
