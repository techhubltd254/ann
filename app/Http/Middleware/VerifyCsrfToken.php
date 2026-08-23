<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    protected $except = [
        'api/mpesa/callback',
        'api/webhooks/n8n',
        'api/stripe/webhook',
        'api/courier/webhook',
        'api/ussd/callback',
    ];
}