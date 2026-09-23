    <style>
        .kicc-wrap{font-size:13px}
        .kicc-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:10px;margin-bottom:16px}
        .kicc-card{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:12px 14px}
        .kicc-card .k{font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:#6b7280}
        .kicc-card .v{font-size:22px;font-weight:700;color:#111827;margin-top:2px}
        .kicc-h{font-size:14px;font-weight:700;margin:18px 0 8px;color:#111827}
        table.kicc{width:100%;border-collapse:collapse;background:#fff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden}
        table.kicc th{background:#f9fafb;text-align:left;padding:7px 9px;font-size:11px;text-transform:uppercase;color:#6b7280;border-bottom:1px solid #e5e7eb}
        table.kicc td{padding:7px 9px;border-bottom:1px solid #f3f4f6}
        .pill{display:inline-block;padding:1px 8px;border-radius:999px;font-size:11px;font-weight:600}
        .ok{background:#d1fae5;color:#065f46}.bad{background:#ffe4e6;color:#9f1239}.warn{background:#fef3c7;color:#92400e}.mut{background:#e5e7eb;color:#374151}
    </style>

    <div class="kicc-wrap">
        @php
            $m = $bus['metrics'] ?? [];
            $g = $graph['summary'] ?? [];
        @endphp

        <div class="kicc-grid">
            <div class="kicc-card"><div class="k">Bus status</div><div class="v">
                @if($bus['ok'] ?? false)<span class="pill ok">ONLINE</span>@else<span class="pill bad">OFFLINE</span>@endif
            </div></div>
            <div class="kicc-card"><div class="k">Pipelines</div><div class="v">{{ $g['nodes'] ?? '—' }}</div></div>
            <div class="kicc-card"><div class="k">Dependency edges</div><div class="v">{{ $g['edges'] ?? '—' }}</div></div>
            <div class="kicc-card"><div class="k">Cycles</div><div class="v">{{ $g['cycles'] ?? '—' }}</div></div>
            <div class="kicc-card"><div class="k">Layers</div><div class="v">{{ $g['layers'] ?? '—' }}</div></div>
            <div class="kicc-card"><div class="k">Events published</div><div class="v">{{ $m['published'] ?? '—' }}</div></div>
            <div class="kicc-card"><div class="k">Deliveries</div><div class="v">{{ $m['delivered'] ?? '—' }}</div></div>
            <div class="kicc-card"><div class="k">Deduped</div><div class="v">{{ $m['deduped'] ?? '—' }}</div></div>
            <div class="kicc-card"><div class="k">Dead-lettered</div><div class="v">{{ $m['deadLettered'] ?? count($dlq) }}</div></div>
        </div>

        <div class="kicc-h">Control</div>
        <table class="kicc">
            <tr><th style="width:150px">Action</th><th>Input</th><th style="width:130px"></th></tr>
            <tr>
                <td>Settle &amp; cascade</td>
                <td><input wire:model="rootPipelines" placeholder="1" style="width:100%;padding:5px 8px;border:1px solid #d1d5db;border-radius:6px"></td>
                <td><button wire:click="triggerCascade" class="pill ok" style="padding:5px 12px">Run cascade</button></td>
            </tr>
            <tr>
                <td>Run automation node</td>
                <td>
                    <select wire:model="nodeKey" style="width:100%;padding:5px 8px;border:1px solid #d1d5db;border-radius:6px">
                        <option value="">— select —</option>
                        @foreach($nodes as $n)<option value="{{ $n['key'] }}">{{ $n['name'] }}</option>@endforeach
                    </select>
                </td>
                <td><button wire:click="runNode" class="pill ok" style="padding:5px 12px">Run node</button></td>
            </tr>
            <tr>
                <td>Retry dead-letter</td>
                <td><input wire:model="dlqKey" placeholder="pipeline id" style="width:100%;padding:5px 8px;border:1px solid #d1d5db;border-radius:6px"></td>
                <td><button wire:click="retryDlq" class="pill warn" style="padding:5px 12px">Retry</button></td>
            </tr>
        </table>

        <div class="kicc-h">Edit — automation nodes (pipeline ids + enable)</div>
        <table class="kicc">
            <tr><th>Node</th><th>Schedule</th><th>Workflow</th><th style="width:220px">Pipeline ids</th><th style="width:80px">Enabled</th></tr>
            @foreach($nodes as $n)
                <tr>
                    <td><strong>{{ $n['name'] }}</strong><br><span class="mut pill">{{ $n['key'] }}</span></td>
                    <td>{{ $n['schedule'] }}</td>
                    <td>{{ $n['workflow'] }}</td>
                    <td><input wire:model="nodePipelineIds.{{ $n['key'] }}" style="width:100%;padding:4px 7px;border:1px solid #d1d5db;border-radius:6px"></td>
                    <td><input type="checkbox" wire:model="nodeEnabled.{{ $n['key'] }}"></td>
                </tr>
            @endforeach
        </table>
        <div style="margin:8px 0 18px"><button wire:click="saveNodeConfig" class="pill ok" style="padding:6px 14px">Save node config</button></div>

        <div class="kicc-h">Monitor — recent automation runs</div>
        <table class="kicc">
            <tr><th>Node</th><th>Status</th><th>Trigger</th><th>Settled</th><th>DLQ</th><th>Duration</th><th>Started</th></tr>
            @forelse($runs as $r)
                <tr>
                    <td>{{ $r->node_name }}</td>
                    <td><span class="pill {{ $r->status === 'succeeded' ? 'ok' : ($r->status === 'failed' ? 'bad' : 'warn') }}">{{ strtoupper($r->status) }}</span></td>
                    <td>{{ $r->trigger }}</td>
                    <td>{{ $r->settled_count }}</td>
                    <td>{{ $r->dlq_count }}</td>
                    <td>{{ $r->duration_ms }} ms</td>
                    <td>{{ optional($r->started_at)->diffForHumans() }}</td>
                </tr>
            @empty
                <tr><td colspan="7" style="color:#6b7280">No runs recorded yet.</td></tr>
            @endforelse
        </table>

        <div class="kicc-h">Monitor — settlement ledger (latest 25)</div>
        <table class="kicc">
            <tr><th>Pipeline</th><th>Mechanism</th><th>Value KES</th><th>Commission KES</th><th>Correlation</th></tr>
            @forelse($ledger as $row)
                <tr>
                    <td>#{{ $row['pipeline_id'] ?? '—' }}</td>
                    <td>{{ $row['mechanism'] ?? '—' }}</td>
                    <td>{{ isset($row['value_kes']) ? number_format((float) $row['value_kes']) : '—' }}</td>
                    <td>{{ isset($row['commission_kes']) ? number_format((float) $row['commission_kes']) : '—' }}</td>
                    <td><code style="font-size:11px">{{ $row['correlationId'] ?? '—' }}</code></td>
                </tr>
            @empty
                <tr><td colspan="5" style="color:#6b7280">Ledger empty — run a cascade.</td></tr>
            @endforelse
        </table>

        <div class="kicc-h">Monitor — dead-letter queue</div>
        <table class="kicc">
            <tr><th>Pipeline</th><th>Reason</th></tr>
            @forelse($dlq as $row)
                <tr><td>#{{ $row['key'] ?? '—' }}</td><td>{{ $row['reason'] ?? '—' }}</td></tr>
            @empty
                <tr><td colspan="2" style="color:#6b7280">DLQ empty — no failed triggers.</td></tr>
            @endforelse
        </table>
    </div>
