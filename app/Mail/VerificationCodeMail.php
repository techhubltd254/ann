<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public string $purpose = 'registration'
    ) {}

    public function build(): self
    {
        $action = $this->purpose === 'login' ? 'sign in' : 'verify your account';
        return $this->subject("Your KICC verification code: {$this->code}")
            ->html("
                <div style='font-family:Inter,Arial,sans-serif;max-width:480px;margin:auto;padding:32px;border:1px solid #eee;border-radius:16px'>
                    <h2 style='margin:0 0 8px;color:#111'>KICC Platform</h2>
                    <p style='color:#555;font-size:14px'>Use this code to {$action}. It expires in 10 minutes.</p>
                    <div style='font-size:34px;font-weight:800;letter-spacing:8px;color:#046bd2;text-align:center;padding:20px 0'>{$this->code}</div>
                    <p style='color:#999;font-size:12px'>If you didn't request this code, you can ignore this email.</p>
                </div>
            ");
    }
}
