<?php

namespace App\Console\Commands;

use App\Services\CountyClassificationService;
use Illuminate\Console\Command;

class PoolClassifyCounties extends Command
{
    protected $signature = 'pool:classify-counties';
    protected $description = 'Classify all 47 counties into RPS/FNS quadrants';

    public function handle(CountyClassificationService $service): int
    {
        $service->classifyAll();
        $this->info('All 47 counties classified into quadrants.');
        return 0;
    }
}