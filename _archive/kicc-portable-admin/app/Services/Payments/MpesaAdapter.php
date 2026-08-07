<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MpesaAdapter implements GatewayAdapter
{
    private string $consumerKey;
    private string $consumerSecret;
    private string $passkey;
    private string $shortcode;
    private bool $live;

    public function __construct()
    {
        $this->consumerKey = config('services.mpesa.consumer_key', '');
        $this->consumerSecret = config('services.mpesa.consumer_secret', '');
        $this->passkey = config('services.mpesa.passkey', '');
        $this->shortcode = config('services.mpesa.shortcode', '174379');
        $this->live = config('services.mpesa.live', false);
    }

    public function charge(array $params): array
    {
        $phone = $params['phone'] ?? '';
        $amount = $params['amount'] ?? 0;
        $reference = $params['reference'] ?? 'KICC-' . uniqid();

        $token = $this->getToken();
        if (!$token) {
            return ['success' => false, 'error' => 'Failed to get M-Pesa token'];
        }

        $timestamp = date('YmdHis');
        $password = base64_encode($this->shortcode . $this->passkey . $timestamp);

        $response = Http::withToken($token)->post(
            $this->baseUrl() . '/mpesa/stkpush/v1/processrequest',
            [
                'BusinessShortCode' => $this->shortcode,
                'Password' => $password,
                'Timestamp' => $timestamp,
                'TransactionType' => 'CustomerPayBillOnline',
                'Amount' => (int) $amount,
                'PartyA' => $this->formatPhone($phone),
                'PartyB' => $this->shortcode,
                'PhoneNumber' => $this->formatPhone($phone),
                'CallBackURL' => config('app.url') . '/api/payments/mpesa/callback',
                'AccountReference' => $reference,
                'TransactionDesc' => $params['description'] ?? 'KICC Payment',
            ]
        );

        $body = $response->json();
        Log::info('M-Pesa STK push response', ['response' => $body]);

        if (($body['ResponseCode'] ?? '1') === '0') {
            return [
                'success' => true,
                'gateway_intent_id' => $body['CheckoutRequestID'],
                'raw' => $body,
            ];
        }

        return ['success' => false, 'error' => $body['errorMessage'] ?? 'M-Pesa request failed', 'raw' => $body];
    }

    public function refund(string $intentId, float $amount, string $reason = ''): array
    {
        // B2C reversal
        return ['success' => false, 'error' => 'B2C not yet implemented'];
    }

    public function status(string $intentId): array
    {
        $token = $this->getToken();
        if (!$token) return ['success' => false];

        $timestamp = date('YmdHis');
        $password = base64_encode($this->shortcode . $this->passkey . $timestamp);

        $response = Http::withToken($token)->post(
            $this->baseUrl() . '/mpesa/stkpushquery/v1/query',
            [
                'BusinessShortCode' => $this->shortcode,
                'Password' => $password,
                'Timestamp' => $timestamp,
                'CheckoutRequestID' => $intentId,
            ]
        );

        return ['success' => true, 'data' => $response->json()];
    }

    private function getToken(): ?string
    {
        $response = Http::withBasicAuth($this->consumerKey, $this->consumerSecret)
            ->get($this->baseUrl() . '/oauth/v1/generate?grant_type=client_credentials');

        return $response->json('access_token');
    }

    private function baseUrl(): string
    {
        return $this->live ? 'https://api.safaricom.co.ke' : 'https://sandbox.safaricom.co.ke';
    }

    private function formatPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone) === 9) $phone = '254' . $phone;
        if (strpos($phone, '0') === 0) $phone = '254' . substr($phone, 1);
        return $phone;
    }
}
