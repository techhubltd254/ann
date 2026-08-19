<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
class OpenApiController extends Controller
{
    public function spec(): JsonResponse
    {
        $path = base_path('docs/openapi.yaml');
        if (! file_exists($path)) {
            return response()->json(['error' => 'Spec not found'], 404);
        }
        $content = file_get_contents($path);
        return response()->json(['spec' => $content]);
    }
}
