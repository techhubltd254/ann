<?php

namespace App\Http\Middleware;

use App\Services\ImageOptimizer;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class OptimizeUploadedImages
{
    private array $pendingOptimizations = [];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $this->collectUploadedFiles($request);

        return $response;
    }

    public function terminate(Request $request, Response $response): void
    {
        if (empty($this->pendingOptimizations)) return;

        try {
            $optimizer = app(ImageOptimizer::class);

            foreach ($this->pendingOptimizations as $path) {
                if (file_exists($path)) {
                    $optimizer->optimize($path, dirname($path));
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Image optimization (terminate): ' . $e->getMessage());
        }
    }

    private function collectUploadedFiles(Request $request): void
    {
        $tempDir = ini_get('upload_tmp_dir') ?: sys_get_temp_dir();

        foreach ($request->allFiles() as $key => $files) {
            $files = is_array($files) ? $files : [$files];
            foreach ($files as $file) {
                if ($file && $file->isValid() && str_starts_with($file->getMimeType() ?: '', 'image/')) {
                    $realPath = $file->getRealPath();
                    if ($realPath && file_exists($realPath)) {
                        $this->pendingOptimizations[] = $realPath;
                    }
                }
            }
        }
    }
}