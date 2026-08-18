<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UssdService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Africa's Talking USSD callback.
 *
 * AT sends a form POST (sessionId, serviceCode, phoneNumber, text) and expects
 * a plain-text body starting with "CON " (continue) or "END " (terminate).
 * Protected by a shared callback token so only AT can reach it.
 */
class UssdController extends Controller
{
    public function handle(Request $request): Response
    {
        $token = config('services.africastalking.ussd_callback_token');

        if ($token && ! hash_equals($token, (string) $request->input('token'))) {
            Log::warning('ussd callback: invalid token', ['ip' => $request->ip()]);
            return response('Unauthorized', 401);
        }

        $phone = (string) $request->input('phoneNumber');
        $text = (string) $request->input('text', '');

        $reply = app(UssdService::class)($phone, $text);

        Log::info('ussd callback', [
            'session' => $request->input('sessionId'),
            'code' => $request->input('serviceCode'),
            'phone' => $phone,
            'text' => $text,
        ]);

        return response($reply)->header('Content-Type', 'text/plain');
    }
}
