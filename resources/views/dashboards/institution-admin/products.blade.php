<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-white">Products</h1>
            <p class="text-zinc-500 text-sm">Marketplace + County listings</p>
        </div>
        <button class="btn-primary text-xs" @click="openDrawer('add-product')">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Product
        </button>
    </div>

    <div class="glass-card rounded-2xl overflow-hidden">
        <div class="flex items-center gap-2 px-5 py-3 border-b border-white/5">
            @foreach(['All','Active','Draft','Low Stock'] as $f)
            <button class="px-3 py-1.5 rounded-full text-xs font-medium transition {{ $loop->first ? 'bg-indigo-500/20 text-indigo-400' : 'text-zinc-400 hover:text-zinc-200 hover:bg-white/5' }}">{{ $f }}</button>
            @endforeach
        </div>
        <table class="w-full text-xs">
            <thead>
                <tr class="text-zinc-500 border-b border-white/5">
                    <th class="text-left py-3.5 px-5 font-semibold">Product</th>
                    <th class="text-left py-3.5 font-semibold">Price</th>
                    <th class="text-center py-3.5 font-semibold">Video</th>
                    <th class="text-left py-3.5 font-semibold">Status</th>
                    <th class="text-center py-3.5 font-semibold">Stock</th>
                    <th class="text-right py-3.5 pr-5 font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @forelse($marketplaceProducts as $p)
                <tr class="hover:bg-white/5 transition cursor-pointer" @click="openDrawer('product')">
                    <td class="py-3.5 px-5">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-white/5 flex items-center justify-center overflow-hidden">
                                @if($p->images->first())
                                <img src="{{ $p->images->first()->url }}" class="w-full h-full object-cover">
                                @else
                                <svg class="w-4 h-4 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                @endif
                            </div>
                            <div>
                                <div class="font-medium text-zinc-200">{{ $p->name }}</div>
                                <div class="text-[10px] text-zinc-500">{{ $p->sku ?? '—' }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="py-3.5 font-semibold text-zinc-200">KES {{ number_format($p->variants->min('price') ?? 0) }}</td>
                    <td class="py-3.5 text-center">
                        @if($p->video_url)
                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">🎬 Reel</span>
                        @else
                        <span class="text-[10px] text-zinc-600">—</span>
                        @endif
                    </td>
                    <td class="py-3.5"><span class="status-active">Active</span></td>
                    <td class="py-3.5 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <div class="progress-bar w-16">
                                <div class="progress-fill bg-emerald-500" style="width: {{ min(($p->variants->sum('stock') ?? 50) / 2, 100) }}%"></div>
                            </div>
                            <span class="text-zinc-400 text-[10px]">{{ $p->variants->sum('stock') ?? '∞' }}</span>
                        </div>
                    </td>
                    <td class="py-3.5 pr-5 text-right">
                        <button class="btn-ghost text-xs py-1 px-2" @click.stop="openDrawer('product')">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/></svg>
                        </button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="py-12 text-center text-zinc-500 text-sm">No products yet. Add your first product.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Add product inline form --}}
    <div class="glass-card rounded-2xl p-5" x-show="drawer === 'add-product'" x-cloak>
        <h3 class="text-sm font-bold text-white mb-4">Add New Product</h3>
        <form method="POST" action="{{ route('institution.admin.products.store', $institution->slug) }}" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @csrf
            <input name="name" required placeholder="Product name">
            <input name="price" type="number" step="0.01" required placeholder="Price (KES)">
            <input name="unit" placeholder="Unit (kg, 500ml)">
            <input name="category" placeholder="Category">
            <input name="stock" type="number" min="0" placeholder="Stock">
            <input name="image" type="file" accept="image/*">
            <input name="video" type="file" accept="video/mp4,video/webm" placeholder="Product video/reel">
            <textarea name="description" rows="2" placeholder="Description" class="md:col-span-3"></textarea>
            <div class="flex gap-2">
                <button class="btn-primary">Add & Sync</button>
                <button type="button" class="btn-ghost" @click="drawer = null">Cancel</button>
            </div>
        </form>
    </div>
</div>