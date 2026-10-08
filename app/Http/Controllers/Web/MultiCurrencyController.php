<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MultiCurrencyController extends Controller
{
    public function settings()
    {
        $currencies = ['KES' => 1, 'USD' => 0.0078, 'EUR' => 0.0072, 'GBP' => 0.0062, 'UGX' => 28.5, 'TZS' => 18.2, 'RWF' => 10.1];
        return view('experience.pages.multi-currency.settings', compact('currencies'));
    }

    public function convert(Request $request)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0',
            'from' => 'required|string|size:3',
            'to' => 'required|string|size:3',
        ]);
        $rates = ['KES' => 1, 'USD' => 0.0078, 'EUR' => 0.0072, 'GBP' => 0.0062, 'UGX' => 28.5, 'TZS' => 18.2, 'RWF' => 10.1];
        $kesAmount = $data['amount'] / ($rates[$data['from']] ?? 1);
        $converted = $kesAmount * ($rates[$data['to']] ?? 1);
        return response()->json([
            'from' => $data['from'], 'to' => $data['to'],
            'original' => $data['amount'], 'converted' => round($converted, 2),
        ]);
    }
}
