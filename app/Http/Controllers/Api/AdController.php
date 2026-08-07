<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AdService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdController extends Controller
{
    public function serve(Request $request, string $placement): JsonResponse
    {
        $ad = AdService::serve($placement, $request->user()?->id, $request->headers->get('referer'));
        if (! $ad) {
            return response()->json(['ad' => null], 200);
        }
        return response()->json(['ad' => $ad]);
    }

    public function click(int $creative)
    {
        $to = AdService::click($creative);
        $target = request()->query('to') ?: $to;
        return redirect()->away($target ?: '/');
    }
}
