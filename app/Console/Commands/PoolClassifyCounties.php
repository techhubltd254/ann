<?php

namespace App\Console\Commands;

use App\Models\County;
use App\Services\AlgorithmsClient;
use App\Services\CountyClassificationService;
use Illuminate\Console\Command;

class PoolClassifyCounties extends Command
{
    protected $signature = 'pool:classify-counties';
    protected $description = 'Classify all 47 counties via Python algorithms service';

    public function handle(): int
    {
        $client = app(AlgorithmsClient::class);

        County::chunk(50, function ($counties) use ($client) {
            foreach ($counties as $county) {
                $params = [
                    'name' => $county->name,
                    'gmv' => (float) $county->gmv,
                    'tourism_bookings' => (float) $county->tourism_bookings,
                    'agri_exports' => (float) $county->agri_exports,
                    'sez_pipeline' => (float) $county->sez_pipeline,
                    'procurement_flow' => (float) $county->procurement_flow,
                    'water' => (float) $county->water,
                    'health' => (float) $county->health,
                    'roads' => (float) $county->roads,
                    'power' => (float) $county->power,
                    'security' => (float) $county->security,
                    'education' => (float) $county->education,
                ];

                $result = $client->classify($county->name, $params);

                $quadrant = $result['quadrant'] ?? 'unclassified';
                $rps = $result['rps'] ?? 0;
                $fns = $result['fns'] ?? 0;

                $county->forceFill([
                    'classification_rps' => $rps,
                    'classification_fns' => $fns,
                    'classification_quadrant' => $quadrant,
                ])->save();
            }
        });

        $this->info('All 47 counties classified via Python service.');
        return 0;
    }
}