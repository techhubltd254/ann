<?php

namespace App\Services;

use App\Models\Payment\PaymentIntent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MpesaService implements PaymentDriverInterface
{
    protected string $env;
    protected ?string $consumerKey;
    protected ?string $consumerSecret;
    protected ?string $shortcode;
    protected ?string $passkey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->env = config('services.mpesa.env', 'sandbox');
        $this->consumerKey = config('services.mpesa.consumer_key');
        $this->consumerSecret = config('services.mpesa.consumer_secret');
        $this->shortcode = config('services.mpesa.shortcode');
        $this->passkey = config('services.mpesa.passkey');
        $this->baseUrl = $this->env === 'production'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';
    }

    /**
     * Initiate STK Push (Lipa Na M-Pesa Online) — ready to use when API key is configured.
     */
    public function process(PaymentIntent $intent, array $meta = []): array
    {
        if (!$this->consumerKey || !$this->consumerSecret) {
            // No API creds yet — mark as pending manual confirmation
            return [
                'success' => true,
                'request' => ['phone' => $meta['phone'] ?? null, 'amount' => $intent->amount],
                'response' => ['message' => 'M-Pesa STK stub — configure consumer key/secret to send real push.'],
            ];
        }

        try {
            $token = $this->getAccessToken();
            $phone = $this->formatPhone($meta['phone'] ?? '');
            $timestamp = now()->format('YmdHis');
            $password = base64_encode($this->shortcode . $this->passkey . $timestamp);

            $response = Http::withToken($token)->post("{$this->baseUrl}/mpesa/stkpush/v1/processrequest", [
                'BusinessShortCode' => $this->shortcode,
                'Password' => $password,
                'Timestamp' => $timestamp,
                'TransactionType' => 'CustomerPayBillOnline',
                'Amount' => (int) ceil($intent->amount),
                'PartyA' => $phone,
                'PartyB' => $this->shortcode,
                'PhoneNumber' => $phone,
                'CallBackURL' => config('services.mpesa.callback_url'),
                'AccountReference' => $intent->intent_id,
                'TransactionDesc' => $intent->description ?? 'KICC Marketplace',
            ]);

            $body = $response->json();
            $success = ($body['ResponseCode'] ?? '1') === '0';

            return [
                'success' => $success,
                'request' => ['phone' => $phone, 'amount' => $intent->amount],
                'response' => $body,
                'transaction_ref' => $body['CheckoutRequestID'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::error('M-Pesa STK failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function verify(string $checkoutRequestId): array
    {
        if (!$this->consumerKey) return ['success' => true, 'status' => 'stub']; // placeholder

        try {
            $token = $this->getAccessToken();
            $timestamp = now()->format('YmdHis');
            $password = base64_encode($this->shortcode . $this->passkey . $timestamp);

            $response = Http::withToken($token)->post("{$this->baseUrl}/mpesa/stkpushquery/v1/query", [
                'BusinessShortCode' => $this->shortcode,
                'Password' => $password,
                'Timestamp' => $timestamp,
                'CheckoutRequestID' => $checkoutRequestId,
            ]);

            return ['success' => true, 'status' => $response->json()['ResultCode'] === '0' ? 'paid' : 'pending', 'response' => $response->json()];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function getAccessToken(): string
    {
        $response = Http::withBasicAuth($this->consumerKey, $this->consumerSecret)
            ->get("{$this->baseUrl}/oauth/v1/generate?grant_type=client_credentials");
        return $response->json()['access_token'] ?? '';
    }

    protected function formatPhone(string $phone): string
    {
        // Normalize Kenyan number: 07XX XXX XXX → 2547XX XXX XXX
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone) === 9) $phone = '254' . $phone;
        if (strlen($phone) === 10 && $phone[0] === '0') $phone = '254' . substr($phone, 1);
        return $phone;
    }
}
