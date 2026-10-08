@props(['countySlug' => ''])
<div x-data="liveBooths()" x-init="init()" class="card-kicc p-4 mb-6">
    <div class="flex justify-between items-center mb-4">
        <h3 class="font-semibold text-lg" style="color: var(--kicc-navy);">
            <span class="inline-block w-3 h-3 rounded-full mr-2" style="background: var(--kicc-red);" x-show="liveCount > 0"></span>
            <span x-text="liveCount > 0 ? liveCount + ' Live Now' : 'Live Booths'"></span>
        </h3>
        <a href="{{ route('national.index') }}" class="text-sm font-medium hover:underline" style="color: var(--kicc-red);">View All →</a>
    </div>

    <template x-if="loading">
        <div class="flex items-center justify-center py-8">
            <div class="animate-spin w-6 h-6 border-2 rounded-full" style="border-color: var(--kicc-red) transparent var(--kicc-red) transparent;"></div>
        </div>
    </template>

    <template x-if="!loading && booths.length === 0">
        <div class="text-center py-8">
            <p class="text-sm" style="color: var(--kicc-text-light);">No live booths right now. Check back during scheduled events.</p>
        </div>
    </template>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3" x-show="booths.length > 0">
        <template x-for="booth in booths" :key="booth.id">
            <a :href="'/national-exhibition/' + booth.slug" class="flex items-center p-3 rounded-lg hover:bg-gray-50 transition-colors border" style="border-color: var(--kicc-border);">
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-sm truncate" style="color: var(--kicc-navy);" x-text="booth.name"></p>
                    <p class="text-xs" style="color: var(--kicc-text-light);" x-text="booth.pillar"></p>
                </div>
                <span class="ml-2 px-2 py-0.5 rounded-full text-xs font-medium" style="background: rgba(11,11,11,0.1); color: #0B0B0B;">
                    <span class="animate-pulse">●</span> LIVE
                </span>
            </a>
        </template>
    </div>
</div>

@push('scripts')
<script>
function liveBooths() {
    return {
        booths: [],
        liveCount: 0,
        loading: true,
        init() {
            fetch('{{ route("api.live.active-booths") }}')
                .then(r => r.json())
                .then(data => {
                    // Filter by current county
                    const countySlug = '{{ $countySlug ?? '' }}';
const routeCounty = '{{ request()->route("county") ? (is_string(request()->route("county")) ? request()->route("county") : request()->route("county")->slug) : "" }}';
const filterSlug = countySlug || routeCounty || '';
                    this.booths = data.filter(b => {
                        return b.name && (!filterSlug || b.name.toLowerCase().includes(filterSlug.replace('-', ' ')));
                    });
                    this.liveCount = this.booths.length;
                    this.loading = false;
                })
                .catch(() => { this.loading = false; });
        }
    };
}
</script>
@endpush