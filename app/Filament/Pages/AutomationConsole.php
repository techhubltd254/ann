<?php

namespace App\Filament\Pages;

use App\Models\AutomationRun;
use App\Services\AutomationTreeService;
use App\Services\PipelineBusClient;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Pipeline Automation Console — the Mother Admin surface for the inter-pipeline bus.
 *
 * MONITOR : bus health, graph metrics, settled totals, ledger, dead-letter queue
 * CONTROL : trigger a cascade, retry a dead-lettered trigger, run an automation node
 * EDIT    : per-node pipeline ids, enable/disable, retry policy knobs
 */
class AutomationConsole extends Page
{
    protected string $view = 'filament.pages.automation-console';

    protected static ?string $navigationLabel = 'Pipeline Automation';

    protected static string | \UnitEnum | null $navigationGroup = 'Automation';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Pipeline Automation Console';

    /** Live bus snapshot (monitor). */
    public array $bus = [];

    public array $graph = [];

    public array $ledger = [];

    public array $dlq = [];

    /** Control form. */
    public string $rootPipelines = '1';

    public string $nodeKey = '';

    public string $dlqKey = '';

    /** Edit form (per-node pipeline ids + enable flag). */
    public array $nodePipelineIds = [];

    public array $nodeEnabled = [];

    public function mount(): void
    {
        $this->refresh();
        foreach (app(AutomationTreeService::class)->nodes() as $n) {
            $this->nodePipelineIds[$n['key']] = implode(',', $n['pipeline_ids']);
            $this->nodeEnabled[$n['key']] = true;
        }
    }

    public function refresh(): void
    {
        $bus = app(PipelineBusClient::class);
        $this->bus = $bus->status();
        $this->graph = $bus->graph();
        $this->ledger = $bus->ledger(25);
        $this->dlq = $bus->dlq(25);
    }

    /** CONTROL — settle the given roots and cascade into every dependent pipeline. */
    public function triggerCascade(): void
    {
        $roots = array_values(array_filter(array_map('intval', explode(',', $this->rootPipelines))));

        if ($roots === []) {
            Notification::make()->title('No pipeline ids given')->danger()->send();

            return;
        }

        $result = app(PipelineBusClient::class)->cascade($roots);

        AutomationRun::create([
            'node_key' => 'manual-cascade',
            'node_name' => 'Manual cascade from console',
            'status' => ($result['ok'] ?? false) ? 'succeeded' : 'failed',
            'trigger' => 'manual',
            'root_pipeline_ids' => $roots,
            'settled_pipeline_ids' => $result['pipelines'] ?? [],
            'settled_count' => count($result['pipelines'] ?? []),
            'failed_pipeline_ids' => $result['failed'] ?? [],
            'dlq_count' => (int) ($result['dlq'] ?? 0),
            'duration_ms' => (int) ($result['metrics']['elapsed_ms'] ?? 0),
            'error' => $result['ok'] ?? false ? null : (string) ($result['error'] ?? 'failed'),
            'triggered_by' => auth()->id(),
            'started_at' => now(),
            'finished_at' => now(),
        ]);

        Notification::make()
            ->title(($result['ok'] ?? false) ? 'Cascade settled ' . count($result['pipelines'] ?? []) . ' pipelines' : 'Cascade failed')
            ->status(($result['ok'] ?? false) ? 'success' : 'danger')
            ->send();

        $this->refresh();
    }

    /** CONTROL — run one automation-tree node (county-content, sbs, agentic-loop, trade-promotion). */
    public function runNode(): void
    {
        if ($this->nodeKey === '') {
            Notification::make()->title('Pick an automation node')->warning()->send();

            return;
        }

        $run = app(AutomationTreeService::class)->run($this->nodeKey, 'manual', auth()->id());

        Notification::make()
            ->title("Node [{$run->node_key}] {$run->status} — {$run->settled_count} pipelines settled")
            ->status($run->status === 'succeeded' ? 'success' : 'danger')
            ->send();

        $this->refresh();
    }

    /** CONTROL — retry a dead-lettered trigger. */
    public function retryDlq(): void
    {
        if ($this->dlqKey === '') {
            Notification::make()->title('Pick a dead-lettered pipeline')->warning()->send();

            return;
        }

        $result = app(PipelineBusClient::class)->trigger((int) $this->dlqKey);

        Notification::make()
            ->title(($result['ok'] ?? false) ? "Retried pipeline {$this->dlqKey}" : 'Retry failed')
            ->status(($result['ok'] ?? false) ? 'success' : 'danger')
            ->send();

        $this->refresh();
    }

    /** EDIT — persist per-node pipeline ids + enable flags to config. */
    public function saveNodeConfig(): void
    {
        $payload = [];
        foreach ($this->nodePipelineIds as $key => $ids) {
            $payload[$key] = [
                'pipeline_ids' => array_values(array_filter(array_map('intval', explode(',', (string) $ids)))),
                'enabled' => (bool) ($this->nodeEnabled[$key] ?? false),
            ];
        }

        file_put_contents(
            storage_path('app/automation-nodes.json'),
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        Notification::make()->title('Automation node config saved')->success()->send();
    }

    public static function canAccess(): bool
    {
        $u = auth()->user();

        return (bool) ($u && in_array($u->account_type ?? '', ['superadmin', 'admin', 'kicc_admin'], true));
    }

    protected function getViewData(): array
    {
        return [
            'bus' => $this->bus,
            'graph' => $this->graph,
            'ledger' => $this->ledger,
            'dlq' => $this->dlq,
            'nodes' => app(AutomationTreeService::class)->nodes(),
            'runs' => AutomationRun::latest()->limit(15)->get(),
        ];
    }
}
