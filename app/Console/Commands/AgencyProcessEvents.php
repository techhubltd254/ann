<?php

namespace App\Console\Commands;

use App\Services\AgencyDataService;
use Illuminate\Console\Command;

class AgencyProcessEvents extends Command
{
    protected $signature = 'agency:process {--agency= : Process only this agency code}';
    protected $description = 'Process pending agency data events (KEPHIS, CBK, KWS, IFMIS, Ardhisasa, SEZA)';

    public function handle(AgencyDataService $svc): int
    {
        $query = \Illuminate\Support\Facades\DB::table('agency_data_events')
            ->where('status', 'received');

        if ($agency = $this->option('agency')) {
            $query->where('agency_code', strtoupper($agency));
        }

        $events = $query->orderBy('id')->take(100)->get();
        if ($events->isEmpty()) {
            $this->info('No pending events.');
            return 0;
        }

        $count = 0;
        foreach ($events as $event) {
            $payload = json_decode($event->payload, true) ?? [];
            $result = $svc->receive(
                $event->agency_code,
                $event->pipeline_code,
                $event->event_type,
                $payload
            );
            $this->line("  {$event->agency_code}/{$event->event_type} → {$event->pipeline_code}: {$result['status']}");
            $count++;
        }

        $this->info("Processed {$count} agency events.");
        return 0;
    }
}