<?php

namespace App\Jobs;

use App\Models\PipelineJob;
use App\Services\Pipeline\PipelineService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class RunPipelineJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;
    public $tries = 1;
    public $backoff = 30;

    public function __construct(public int $pipelineJobId)
    {
        $this->onConnection(env('PIPELINE_QUEUE_CONNECTION', 'database'));
    }

    public function handle(PipelineService $pipeline): void
    {
        $job = PipelineJob::find($this->pipelineJobId);
        if (!$job || in_array($job->status, ['cancelled', 'completed', 'failed'], true)) {
            return;
        }

        try {
            $engine = $pipeline->engineInstance($job->engine);
            if (!$engine->available()) {
                throw new \RuntimeException('Engine unavailable: ' . $engine->availabilityNote());
            }

            $job->markRunning('starting');
            $engine->run($job);
        } catch (Throwable $e) {
            $job->fail($e);
        }
    }
}
