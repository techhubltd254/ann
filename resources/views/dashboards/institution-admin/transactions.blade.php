<div class="glass-card rounded-2xl p-6">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-bold text-white">Transaction History</h1>
            <p class="text-zinc-500 text-sm">{{ $transactions->count() }} records</p>
        </div>
        <div class="flex items-center gap-2">
            <select class="w-32 text-xs">
                <option>All Status</option>
                <option>Paid</option>
                <option>Pending</option>
            </select>
            <button class="btn-ghost text-xs">Export CSV</button>
        </div>
    </div>
    <table class="w-full text-xs">
        <thead><tr class="text-zinc-500 border-b border-white/5">
            <th class="text-left py-3 font-semibold">ID</th><th class="text-left py-3 font-semibold">Customer</th>
            <th class="text-left py-3 font-semibold">Product</th><th class="text-left py-3 font-semibold">Status</th>
            <th class="text-right py-3 font-semibold">Qty</th><th class="text-right py-3 font-semibold">Amount</th>
            <th class="text-right py-3 font-semibold">Date</th>
        </tr></thead>
        <tbody class="divide-y divide-white/5">
            @forelse($transactions as $tx)
            <tr class="hover:bg-white/5 transition">
                <td class="py-3 font-mono text-zinc-400">#{{ $tx->order_number }}</td>
                <td class="py-3 text-zinc-200">{{ $tx->customer_name ?? 'Guest' }}</td>
                <td class="py-3 text-zinc-400">{{ Str::limit($tx->product_name, 30) }}</td>
                <td class="py-3">
                    @php
                    $ps = $tx->payment_status ?? 'pending';
                    $b = match($ps){'paid','success'=>'status-paid','pending'=>'status-pending','failed'=>'status-failed',default=>'status-draft'};
                    @endphp
                    <span class="{{ $b }}">{{ ucfirst($ps) }}</span>
                </td>
                <td class="py-3 text-right text-zinc-300">{{ $tx->quantity }}</td>
                <td class="py-3 text-right font-semibold text-zinc-200">KES {{ number_format($tx->total) }}</td>
                <td class="py-3 text-right text-zinc-500">{{ \Carbon\Carbon::parse($tx->placed_at)->format('d M Y') }}</td>
            </tr>
            @empty
            <tr><td colspan="7" class="py-12 text-center text-zinc-500 text-sm">No transactions.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>