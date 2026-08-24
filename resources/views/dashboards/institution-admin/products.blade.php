<script>
document.addEventListener('alpine:init', () => {
    Alpine.store('pd', {
        detail: null,
        form: { name: '', price: '', unit: '', category: '', desc: '', stock: '' },
        previewVideo: null,
        previewImage: null,
        open(data) {
            this.detail = data;
            this.form = {
                name: data?.name || '',
                price: data?.price || '',
                unit: data?.unit || '',
                category: data?.category || '',
                desc: data?.desc || '',
                stock: data?.stock || ''
            };
            this.previewVideo = data?.video_url || null;
            this.previewImage = data?.image_url || null;
        },
        close() {
            this.detail = null;
            this.previewVideo = null;
            this.previewImage = null;
            this.form = { name: '', price: '', unit: '', category: '', desc: '', stock: '' };
        },
        videoSelect(event) {
            const file = event.target.files[0];
            if (file) this.previewVideo = URL.createObjectURL(file);
        },
        imageSelect(event) {
            const file = event.target.files[0];
            if (file) this.previewImage = URL.createObjectURL(file);
        }
    });
});
</script>

<div class="space-y-6" x-data="{ filter: 'All', setFilter(f) { this.filter = f; } }">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-white">Products</h1>
            <p class="text-zinc-500 text-sm">Marketplace + County listings</p>
        </div>
        <button class="btn-primary text-xs" @click='$store.pd.open({"name":"","price":"","unit":"","category":"","desc":"","stock":""})'>
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
                    'image_url' => $p->images->first()?->url ?? '',
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
                            <button @click='$store.pd.open({!! $productData !!})' class="btn-ghost text-[10px] py-1 px-1.5 text-zinc-400 hover:text-white" title="Edit product">
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
</div>

{{-- ══════════ PRODUCT PORTFOLIO DRAWER (standalone, global store) ══════════ --}}
<div x-data
     x-show="$store.pd.detail !== null"
     x-cloak
     class="fixed inset-0 z-[99999]">

    <div class="absolute inset-0 bg-black/60" @click="$store.pd.close()"></div>

    <div class="absolute right-0 top-0 h-full w-full max-w-2xl bg-[#161A22]/95 backdrop-blur-md border-l border-white/10 overflow-y-auto p-6 shadow-2xl"
         @click.stop>
        <div class="flex items-center justify-between pb-4 border-b border-white/10 mb-5">
            <div>
                <h3 class="text-lg font-bold text-white" x-text="$store.pd.detail?.id == null ? 'Add New Product' : ($store.pd.detail?.name || 'Product Portfolio')"></h3>
                <p class="text-xs text-zinc-500 mt-0.5" x-show="$store.pd.detail?.id != null" x-text="$store.pd.detail?.sku || ''"></p>
            </div>
            <button @click="$store.pd.close()" class="text-zinc-400 hover:text-white p-2 rounded-lg hover:bg-white/10 transition shrink-0 cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form method="POST"
              :action="$store.pd.detail?.id != null ? '{{ route('institution.admin.products.update', [$institution->slug, '__PRODUCT__']) }}'.replace('__PRODUCT__', $store.pd.detail.id) : '{{ route('institution.admin.products.store', $institution->slug) }}'"
              enctype="multipart/form-data">
            @csrf

            <div x-show="$store.pd.previewVideo" class="aspect-video bg-black rounded-xl overflow-hidden mb-5" style="pointer-events:none">
                <video :src="$store.pd.previewVideo" autoplay muted loop playsinline class="w-full h-full object-cover" style="pointer-events:none"></video>
            </div>
            <div x-show="!$store.pd.previewVideo && $store.pd.previewImage" class="h-48 bg-[#0B0D11] rounded-xl overflow-hidden mb-5" style="pointer-events:none">
                <img :src="$store.pd.previewImage" class="w-full h-full object-cover" style="pointer-events:none">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="md:col-span-2">
                    <label class="text-[10px] font-semibold text-zinc-500 uppercase tracking-widest block mb-1">Product Name</label>
                    <input name="name" x-model="$store.pd.form.name" required>
                </div>
                <div>
                    <label class="text-[10px] font-semibold text-zinc-500 uppercase tracking-widest block mb-1">Price (KES)</label>
                    <input name="price" type="number" step="0.01" x-model="$store.pd.form.price" required>
                </div>
                <div>
                    <label class="text-[10px] font-semibold text-zinc-500 uppercase tracking-widest block mb-1">Unit</label>
                    <input name="unit" x-model="$store.pd.form.unit" placeholder="kg, 500ml, piece">
                </div>
                <div>
                    <label class="text-[10px] font-semibold text-zinc-500 uppercase tracking-widest block mb-1">Category</label>
                    <input name="category" x-model="$store.pd.form.category" placeholder="Food, Beverage...">
                </div>
                <div>
                    <label class="text-[10px] font-semibold text-zinc-500 uppercase tracking-widest block mb-1">Stock</label>
                    <input name="stock" type="number" min="0" x-model="$store.pd.form.stock">
                </div>
                <div>
                    <label class="text-[10px] font-semibold text-zinc-500 uppercase tracking-widest block mb-1">Product Image</label>
                    <input name="image" type="file" accept="image/*" @change="$store.pd.imageSelect">
                </div>
                <div>
                    <label class="text-[10px] font-semibold text-zinc-500 uppercase tracking-widest block mb-1">Product Video / Reel</label>
                    <input name="video" type="file" accept="video/mp4,video/webm" @change="$store.pd.videoSelect">
                    <p class="text-[9px] text-zinc-600 mt-1" x-show="$store.pd.detail?.video_url" x-text="'Current: ' + ($store.pd.detail?.video_url?.split('/').pop() || 'none')"></p>
                </div>
                <div class="md:col-span-2">
                    <label class="text-[10px] font-semibold text-zinc-500 uppercase tracking-widest block mb-1">Description</label>
                    <textarea name="description" rows="3" x-model="$store.pd.form.desc"></textarea>
                </div>
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-white/10">
                <div class="flex items-center gap-3">
                    <button type="submit" class="btn-primary cursor-pointer">
                        <span x-text="$store.pd.detail?.id == null ? 'Done — Add Product' : 'Done — Update & Sync'"></span>
                    </button>
                    <button type="button" class="btn-ghost cursor-pointer" @click="$store.pd.close()">Cancel</button>
                </div>
                <span x-show="$store.pd.detail?.id != null" class="text-[10px] text-zinc-500">Auto-syncs to marketplace</span>
            </div>
        </form>
    </div>
</div>