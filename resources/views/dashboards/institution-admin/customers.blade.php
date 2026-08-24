<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h1 class="text-xl font-bold text-white">Customers</h1><p class="text-zinc-500 text-sm">{{ $customers->count() }} total buyers</p></div>
    </div>
    <div class="glass-card rounded-2xl p-5">
        <table class="w-full text-xs">
            <thead><tr class="text-zinc-500 border-b border-white/5">
                <th class="text-left py-3 font-semibold">Name</th>
                <th class="text-left py-3 font-semibold">Contact</th>
                <th class="text-center py-3 font-semibold">Orders</th>
                <th class="text-right py-3 font-semibold">Total Spent</th>
            </tr></thead>
            <tbody class="divide-y divide-white/5">
                @forelse($customers as $c)
                <tr class="hover:bg-white/5 transition">
                    <td class="py-3 font-medium text-zinc-200">{{ $c->name }}</td>
                    <td class="py-3 text-zinc-400">{{ $c->email ?? $c->phone }}</td>
                    <td class="py-3 text-center text-zinc-300">{{ $c->orders_count }}</td>
                    <td class="py-3 text-right font-semibold text-indigo-400">KES {{ number_format($c->total_spent) }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="py-12 text-center text-zinc-500 text-sm">No customers.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>