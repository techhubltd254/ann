<?php

namespace App\Console\Commands;

use App\Http\Controllers\Admin\RecordsHierarchyController;
use Illuminate\Console\Command;

class MirrorKiccHierarchy extends Command
{
    protected $signature = 'kicc:mirror-hierarchy';

    protected $description = 'Mirror counties, sectors and institutions into publishing records and link county → sector → institution (legacy records model)';

    public function handle(): int
    {
        $this->info('Mirroring native hierarchy into publishing records…');
        app(RecordsHierarchyController::class)->mirror();

        $this->info('Done. Re-run any time — the pass is idempotent.');

        return self::SUCCESS;
    }
}
