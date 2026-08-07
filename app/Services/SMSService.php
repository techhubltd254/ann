<?php

namespace App\Services;

use AfricasTalking\SDK\AfricasTalking;
use Illuminate\Support\Facades\Log;

class SMSService
{
    protected ?AfricasTalking $client = null;
    protected ?string $from = null;

    public function __construct()
    {
        $username = config('services.africastalking.username');
        $apiKey = config('services.africastalking.key');
        $this->from = config('services.africastalking.from');

        if ($username && $apiKey && $username !== 'sandbox') {
            try {
                $this->client = new AfricasTalking($username, $apiKey);
            } catch (\Exception $e) {
                Log::warning('Africa\'s Talking init failed: ' . $e->getMessage());
            }
        }
    }

    public function send(string $phone, string $message): bool
    {
        if ($this->client) {
            try {
                $sms = $this->client->sms();
                $result = $sms->send([
                    'to' => $this->formatPhone($phone),
                    'message' => $message,
                    'from' => $this->from,
                ]);
                $status = ($result['status'] ?? 'failure') === 'success';
                Log::info("SMS sent to {$phone}: " . ($status ? 'OK' : 'FAILED'));
                return $status;
            } catch (\Exception $e) {
                Log::error("SMS failed to {$phone}: " . $e->getMessage());
                return false;
            }
        }

        Log::info("SMS would be sent to {$phone}: {$message}");
        return true;
    }

    public function sendVerificationCode(string $phone, string $code): bool
    {
        $message = "Your KICC verification code is: {$code}. Valid for 10 minutes.";
        return $this->send($phone, $message);
    }

    protected function formatPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone) === 9) {
            $phone = '254' . $phone;
        } elseif (strlen($phone) === 10 && str_starts_with($phone, '0')) {
            $phone = '254' . substr($phone, 1);
        }
        return '+' . $phone;
    }
}
