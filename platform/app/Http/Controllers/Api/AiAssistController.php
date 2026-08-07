<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AiUxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiAssistController extends Controller
{
    public function __construct(private readonly AiUxService $ux)
    {
    }

    public function uxAssist(Request $request): JsonResponse
    {
        $context = $request->string('context', 'media_library')->toString();

        return response()->json($this->ux->suggestions($context));
    }
}
