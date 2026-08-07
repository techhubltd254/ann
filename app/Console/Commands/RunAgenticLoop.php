<?php

namespace App\Console\Commands;

use App\Services\AgenticLoopService;
use Illuminate\Console\Command;

class RunAgenticLoop extends Command
{
    protected $signature = 'agentic:run {--cycle : Full O-D-A cycle} {--recommend= : County slug to refresh recommendations}';
    protected $description = 'Run the Agentic Loop automation engine';

    public function handle(AgenticLoopService $loop): int
    {
        if ($county = $this->option('recommend')) {
            $result = $loop->refreshCountyRecommendations($county);
            $this->line(json_encode($result));
            return Command::SUCCESS;
        }

        $this->info('Running Agentic Loop (Observer → Decider → Actor)...');
        $result = $loop->run();
        $this->info('Observed: ' . $result['observed']);
        foreach ($result['results'] as $r) {
            $this->line("  Action: {$r['action']} → {$r['status']}");
        }
        return Command::SUCCESS;
    }
}