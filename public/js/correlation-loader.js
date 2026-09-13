document.addEventListener('alpine:init', () => {
    Alpine.data('correlationLoader', (type, id) => ({
        loading: false,
        loaded: false,
        html: '',
        loadMore() {
            this.loading = true;
            fetch('/api/correlations/' + type + '/' + id)
                .then(r => r.json())
                .then(data => {
                    this.html = this.renderMore(data);
                    this.loaded = true;
                    this.loading = false;
                })
                .catch(() => { this.loading = false; });
        },
        renderMore(data) {
            var h = '';
            if (data.places_to_visit && data.places_to_visit.length > 0) {
                h += '<div class="mb-6"><h4 class="text-sm font-bold text-gray-900 mb-3"> More Places</h4><div class="grid grid-cols-2 md:grid-cols-4 gap-4">';
                data.places_to_visit.forEach(function(r) {
                    h += '<a href="/counties/institution/' + r.slug + '" class="bg-white border border-gray-200 rounded-xl p-3 hover:border-amber-300 transition-all"><div class="font-bold text-sm">' + r.name + '</div><div class="text-xs text-gray-500">' + (r.distance_km || '') + ' km · ' + (r.type_label || '') + '</div></a>';
                });
                h += '</div></div>';
            }
            if (data.places_to_stay && data.places_to_stay.length > 0) {
                h += '<div class="mb-6"><h4 class="text-sm font-bold text-gray-900 mb-3"> More Places to Stay</h4><div class="grid grid-cols-2 md:grid-cols-3 gap-4">';
                data.places_to_stay.forEach(function(r) {
                    h += '<a href="/counties/institution/' + r.slug + '" class="bg-white border border-gray-200 rounded-xl p-3 hover:border-amber-300 transition-all"><div class="font-bold text-sm">' + r.name + '</div><div class="text-xs text-gray-500">' + (r.distance_km || '') + ' km</div></a>';
                });
                h += '</div></div>';
            }
            if (data.transport && data.transport.length > 0) {
                h += '<div><h4 class="text-sm font-bold text-gray-900 mb-3"> More Transport</h4><div class="grid grid-cols-2 md:grid-cols-4 gap-4">';
                data.transport.forEach(function(t) {
                    var priceHtml = '';
                    if (t.price) priceHtml = '<div class="font-bold text-amber-600 text-sm">KES ' + t.price.toLocaleString() + '</div>';
                    h += '<div class="bg-white border border-gray-200 rounded-xl p-3"><div class="font-bold text-sm">' + t.name + '</div>' + priceHtml + '<div class="text-xs text-gray-500">' + (t.type_label || '') + '</div></div>';
                });
                h += '</div></div>';
            }
            return h;
        }
    }));
});