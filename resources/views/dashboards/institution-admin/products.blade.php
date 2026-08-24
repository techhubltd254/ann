<div class="space-y-6" x-data="{
    detail: null,
    detailIndex: null,
    form: { name: '', price: '', unit: '', category: '', desc: '', stock: '' },
    filter: 'All',
    setFilter(f) { this.filter = f; },
    openDetail(index, data) {
        this.detail = data;
        this.detailIndex = index;
        this.form = { 
            name: data?.name || '', 
            price: data?.price || '', 
            unit: data?.unit || '', 
            category: data?.category || '', 
            desc: data?.desc || '', 
            stock: data?.stock || '' 
        };
    },
    closeDetail() {
        this.detail = null;
        this.detailIndex = null;
    }
}">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-white">Products</h1>
            <p class="text-zinc-500 text-sm">Marketplace + County listings</p>
        </div>
        <button class="btn-primary text-xs" @click="openDetail(-1, {name:'', price:'', unit:'', category:'', desc:'', stock:''})">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Product
        </button>
    </div>

    <div class="glass-card rounded-2xl overflow-hidden">
        <div class="flex items-center gap-2 px-5 py-3 border-b border-white/5">
            <template x-for="f in ['All','Active','Draft','Low Stock']" :key="f">
                <button @click="setFilter(f)"
                    class="px-3 py-1.5 rounded-full text-xs font-medium transition"
                    :class="filter === f ? 'bg-indigo-500/20 text-indigo-400' : 'text-zinc-400 hover:text-zinc-200 hover:bg-white/5'"
                    x-text="f"></button>
            </template>
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
                @forelse($marketplaceProducts as $i => $p)
                @php 
                $instProduct = $institution->products[$i] ?? []; 
                $stockTotal = $p->variants->sum('stock');
                $status = $stockTotal > 0 ? 'Active' : 'Draft';
                $stockLevel = $stockTotal > 100 ? 'high' : ($stockTotal > 10 ? 'medium' : 'low');
                $productData = json_encode([
                    'name' => $p->name,
                    'price' => $p->variants->min('price') ?? 0,
                    'unit' => $p->unit,
                    'category' => $instProduct['category'] ?? '',
                    'desc' => $p->description,
                    'stock' => $stockTotal,
                    'video_url' => $p->video_url,
                    'sku' => $p->sku,
                    'id' => $p->id,
                ]);
                @endphp
                <tr class="hover:bg-white/5 transition"
                    x-show="filter === 'All' || (filter === 'Active' && '{{ $status }}' === 'Active') || (filter === 'Draft' && '{{ $status }}' === 'Draft') || (filter === 'Low Stock' && '{{ $stockLevel }}' === 'low')">
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
                    <td class="py-3.5">
                        @if($stockTotal > 0)
                        <span class="status-active">Active</span>
                        @else
                        <span class="status-draft">Draft</span>
                        @endif
                    </td>
                    <td class="py-3.5 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <div class="progress-bar w-16">
                                <div class="progress-fill {{ $stockLevel === 'low' ? 'bg-red-500' : ($stockLevel === 'medium' ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ min($stockTotal / 10, 100) }}%"></div>
                            </div>
                            <span class="text-zinc-400 text-[10px]">{{ $stockTotal }}</span>
                        </div>
                    </td>
                    <td class="py-3.5 pr-5 text-right">
                        <div class="flex items-center justify-end gap-1">
                            <button @click='openDetail({{ $i }}, {!! $productData !!})' class="btn-ghost text-[10px] py-1 px-1.5 text-zinc-400 hover:text-white" title="Edit product">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            </button>
                            <form method="POST" action="{{ route('institution.admin.products.delete', [$institution->slug, $i]) }}" class="inline" onsubmit="return confirm('Delete?')">
                                @csrf
                                <button class="btn-ghost text-[10px] py-1 px-1.5 text-red-400">🗑️</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="py-12 text-center text-zinc-500 text-sm">No products yet. Add your first product.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ═══ PRODUCT DETAIL PANEL (slide-over) ═══ --}}
    <div x-show="detail !== null" x-cloak x-transition.duration.200ms class="fixed inset-0 z-50 flex justify-end">
        <div class="absolute inset-0 bg-black/60" @click="closeDetail()"></div>
        <div class="relative w-full max-w-2xl bg-[#161A22]/95 backdrop-blur-md border-l border-white/10 overflow-y-auto p-6 shadow-2xl">
            {{-- Header --}}
            <div class="flex items-center justify-between pb-4 border-b border-white/10 mb-6">
                <div>
                    <h3 class="text-lg font-bold text-white" x-text="detailIndex === -1 ? 'Add New Product' : (detail?.name || 'Product Details')"></h3>
                    <p class="text-xs text-zinc-500 mt-0.5" x-show="detailIndex !== -1" x-text="detail?.sku || ''"></p>
                </div>
                <button @click="closeDetail()" class="text-zinc-400 hover:text-white p-1.5 rounded-lg hover:bg-white/5 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Video Preview --}}
            <div x-show="detail?.video_url" class="aspect-video bg-black rounded-xl overflow-hidden mb-5">
                <video autoplay muted loop playsinline class="w-full h-full object-cover">
                    <source :src="detail?.video_url" type="video/mp4">
                </video>
            </div>

            {{-- Edit Form --}}
            <form method="POST" :action="detailIndex !== -1 && detailIndex !== null ? '{{ route('institution.admin.products.update', [$institution->slug, '__INDEX__']) }}'.replace('__INDEX__', detailIndex) : '{{ route('institution.admin.products.store', $institution->slug) }}'"
                  enctype="multipart/form-data">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div class="md:col-span-2">
                        <label class="text-[10px] font-semibold text-zinc-500 uppercase tracking-widest block mb-1">Product Name</label>
                        <input name="name" x-model="form.name" required>
                    </div>
                    <div>
                        <label class="text-[10px] font-semibold text-zinc-500 uppercase tracking-widest block mb-1">Price (KES)</label>
                        <input name="price" type="number" step="0.01" x-model="form.price" required>
                    </div>
                    <div>
                        <label class="text-[10px] font-semibold text-zinc-500 uppercase tracking-widest block mb-1">Unit</label>
                        <input name="unit" x-model="form.unit" placeholder="kg, 500ml, piece">
                    </div>
                    <div>
                        <label class="text-[10px] font-semibold text-zinc-500 uppercase tracking-widest block mb-1">Category</label>
                        <input name="category" x-model="form.category" placeholder="Food, Beverage...">
                    </div>
                    <div>
                        <label class="text-[10px] font-semibold text-zinc-500 uppercase tracking-widest block mb-1">Stock</label>
                        <input name="stock" type="number" min="0" x-model="form.stock">
                    </div>
                    <div>
                        <label class="text-[10px] font-semibold text-zinc-500 uppercase tracking-widest block mb-1">Product Image</label>
                        <input name="image" type="file" accept="image/*">
                    </div>
                    <div>
                        <label class="text-[10px] font-semibold text-zinc-500 uppercase tracking-widest block mb-1">Product Video / Reel</label>
                        <input name="video" type="file" accept="video/mp4,video/webm">
                        <p class="text-[9px] text-zinc-600 mt-1" x-show="detail?.video_url" x-text="'Current: ' + (detail?.video_url?.split('/').pop() || 'none')"></p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-[10px] font-semibold text-zinc-500 uppercase tracking-widest block mb-1">Description</label>
                        <textarea name="description" rows="3" x-model="form.desc"></textarea>
                    </div>
                </div>
                <div class="flex items-center justify-between pt-4 border-t border-white/10">
                    <div class="flex items-center gap-3">
                        <button type="submit" class="btn-primary">
                            <span x-text="detailIndex === -1 ? '✅ Done — Add Product' : '✅ Done — Update & Sync'"></span>
                        </button>
                        <button type="button" class="btn-ghost" @click="closeDetail()">Cancel</button>
                    </div>
                    <span x-show="detailIndex !== -1 && detailIndex !== null" class="text-[10px] text-zinc-500">Auto-syncs to marketplace</span>
                </div>
            </form>
        </div>
    </div>
</div>