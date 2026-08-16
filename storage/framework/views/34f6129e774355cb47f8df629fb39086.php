<?php $__env->startSection('title', 'KICC Overall Admin — Platform Control'); ?>

<?php $__env->startSection('content'); ?>
<?php $accent = '#F59E0B'; ?>
<div class="flex min-h-screen bg-[#F9FAFB]">
    
    <div class="w-56 bg-white border-r border-gray-200 flex flex-col shrink-0 min-h-screen">
        <div class="flex items-center gap-3 px-4 border-b border-gray-200 h-16">
            <div class="flex items-center gap-2">
                <img src="<?php echo e(media('kicc/kicc-logo.png')); ?>" alt="KICC" class="h-7 w-auto" style="filter: brightness(0) invert(1);">
                <div class="text-[#FFCD05] text-[9px] font-black tracking-[0.15em] uppercase">Overall<br>Admin</div>
            </div>
        </div>
        <div class="flex-1 py-3 overflow-y-auto">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $navItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
            <a href="<?php echo e(route('kicc.admin', ['tab' => $item['tab']])); ?>"
               class="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-semibold transition-all <?php echo e($tab === $item['tab'] ? 'text-gray-900 border-r-2' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-800'); ?>"
               style="<?php echo e($tab === $item['tab'] ? 'background: '.$accent.'44; border-color: '.$accent : ''); ?>">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo e($item['icon']); ?>"/></svg>
                <span class="truncate text-xs"><?php echo e($item['label']); ?></span>
            </a>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
        </div>
        <div class="px-4 py-3 border-t border-gray-200 text-[10px] text-gray-400 uppercase tracking-widest">Sub-portals</div>
        <a href="<?php echo e(route('national.admin')); ?>" class="px-4 py-2 text-xs text-gray-400 hover:text-gray-700 transition-colors">National Portal &nearr;</a>
        <a href="<?php echo e(route('county.admin')); ?>" class="px-4 py-2 text-xs text-gray-400 hover:text-gray-700 transition-colors">County Portal &nearr;</a>
        <a href="/" class="flex items-center gap-3 px-4 py-4 border-t border-gray-200 mt-2 text-gray-400 hover:text-gray-700 text-xs transition-colors">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            <span>Exit</span>
        </a>
    </div>

    
    <div class="flex-1 flex flex-col overflow-hidden">
        <div class="bg-white border-b border-gray-200 px-6 h-16 flex items-center justify-between shrink-0">
            <div>
                <div class="font-black text-gray-900 text-sm">KICC Platform Control Center</div>
                <div class="text-[10px] font-bold uppercase tracking-widest" style="color: <?php echo e($accent); ?>">Overall Administrator — All Tiers</div>
            </div>
        </div>
        <div class="flex-1 overflow-y-auto p-6">

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('success')): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl px-5 py-3 mb-6 text-sm"><?php echo e(session('success')); ?></div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($tab === 'overview'): ?>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-gray-900"><?php echo e($stats['counties']); ?></div><div class="text-xs text-gray-400 mt-1">County Exhibitors</div></div>
                <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-gray-900"><?php echo e($stats['ministries']); ?>/<?php echo e($stats['agencies']); ?></div><div class="text-xs text-gray-400 mt-1">Ministries / Agencies</div></div>
                <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-gray-900"><?php echo e($stats['exhibitors']); ?></div><div class="text-xs text-gray-400 mt-1">Private Exhibitors</div></div>
                <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-gray-900"><?php echo e($stats['tradeBoards']); ?></div><div class="text-xs text-gray-400 mt-1">County Trade Boards</div></div>
                <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-gray-900"><?php echo e($stats['products']); ?></div><div class="text-xs text-gray-400 mt-1">Products Live</div></div>
                <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-gray-900"><?php echo e($stats['orders']); ?></div><div class="text-xs text-gray-400 mt-1">Orders</div></div>
                <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black" style="color: <?php echo e($accent); ?>">KES <?php echo e(number_format($stats['escrowTotal'])); ?></div><div class="text-xs text-gray-400 mt-1">Escrow Volume</div></div>
                <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-amber-600">KES <?php echo e(number_format($stats['escrowHeld'])); ?></div><div class="text-xs text-gray-400 mt-1">Held in Escrow</div></div>
            </div>
            <div class="grid md:grid-cols-3 gap-4">
                <a href="<?php echo e(route('kicc.admin', ['tab' => 'counties'])); ?>" class="bg-white border border-gray-200 rounded-2xl p-6 hover:shadow-md transition-all"><div class="font-bold text-gray-900 mb-1">County Tier</div><p class="text-xs text-gray-500">47 independent county websites &amp; trade boards.</p></a>
                <a href="<?php echo e(route('kicc.admin', ['tab' => 'exhibitors'])); ?>" class="bg-white border border-gray-200 rounded-2xl p-6 hover:shadow-md transition-all"><div class="font-bold text-gray-900 mb-1">Private Exhibitor Tier</div><p class="text-xs text-gray-500">Business storefronts with escrow-protected sales.</p></a>
                <a href="<?php echo e(route('kicc.admin', ['tab' => 'national'])); ?>" class="bg-white border border-gray-200 rounded-2xl p-6 hover:shadow-md transition-all"><div class="font-bold text-gray-900 mb-1">National Government Tier</div><p class="text-xs text-gray-500">Ministries &amp; agencies under the national pavilion.</p></a>
            </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($tab === 'portals'): ?>
            <div>
                <h3 class="font-bold text-gray-900 mb-6">All Platform Portals — enter any tier</h3>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    
                    <a href="<?php echo e(route('kicc.admin')); ?>" class="group bg-white rounded-2xl border-2 border-gray-200 hover:border-[#F59E0B] p-6 text-center transition-all hover:shadow-xl">
                        <div class="w-14 h-14 bg-[#F59E0B]/10 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:bg-[#F59E0B]/20 transition-colors">
                            <svg class="w-7 h-7 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                        <h2 class="font-black text-gray-900 text-lg mb-2">KICC Overall Admin</h2>
                        <p class="text-gray-500 text-sm leading-relaxed">Full platform control — all tiers, counties, orders, escrow, users.</p>
                        <div class="mt-4 text-[#F59E0B] text-xs font-bold uppercase tracking-widest">CURRENT</div>
                    </a>
                    
                    <a href="<?php echo e(route('national.admin')); ?>" class="group bg-white rounded-2xl border-2 border-gray-200 hover:border-[#0EA5E9] p-6 text-center transition-all hover:shadow-xl">
                        <div class="w-14 h-14 bg-[#0EA5E9]/10 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:bg-[#0EA5E9]/20 transition-colors">
                            <svg class="w-7 h-7 text-[#0EA5E9]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                        <h2 class="font-black text-gray-900 text-lg mb-2">National Government</h2>
                        <p class="text-gray-500 text-sm">Ministries &amp; agencies exhibitor portal.</p>
                        <div class="mt-4 text-[#0EA5E9] text-xs font-bold uppercase tracking-widest">ENTER &nearr;</div>
                    </a>
                    
                    <a href="<?php echo e(route('counties.index')); ?>" class="group bg-white rounded-2xl border-2 border-gray-200 hover:border-[#14B8A6] p-6 text-center transition-all hover:shadow-xl">
                        <div class="w-14 h-14 bg-[#14B8A6]/10 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:bg-[#14B8A6]/20 transition-colors">
                            <svg class="w-7 h-7 text-[#14B8A6]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <h2 class="font-black text-gray-900 text-lg mb-2">County Portal (47)</h2>
                        <p class="text-gray-500 text-sm">Each county has its own admin — content, images, prices.</p>
                        <div class="mt-4 text-[#14B8A6] text-xs font-bold uppercase tracking-widest">BROWSE COUNTIES &nearr;</div>
                    </a>
                    
                    <a href="<?php echo e(route('exhibitor.admin')); ?>" class="group bg-white rounded-2xl border-2 border-gray-200 hover:border-[#38BDF8] p-6 text-center transition-all hover:shadow-xl">
                        <div class="w-14 h-14 bg-[#38BDF8]/10 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:bg-[#38BDF8]/20 transition-colors">
                            <svg class="w-7 h-7 text-[#38BDF8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        </div>
                        <h2 class="font-black text-gray-900 text-lg mb-2">Private Exhibitors</h2>
                        <p class="text-gray-500 text-sm">Business storefronts with escrow-protected sales.</p>
                        <div class="mt-4 text-[#38BDF8] text-xs font-bold uppercase tracking-widest">ENTER &nearr;</div>
                    </a>
                    
                    <a href="<?php echo e(route('provider.admin')); ?>" class="group bg-white rounded-2xl border-2 border-gray-200 hover:border-[#0EA5E9] p-6 text-center transition-all hover:shadow-xl">
                        <div class="w-14 h-14 bg-[#0EA5E9]/10 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:bg-[#0EA5E9]/20 transition-colors">
                            <svg class="w-7 h-7 text-[#0EA5E9]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        </div>
                        <h2 class="font-black text-gray-900 text-lg mb-2">Travel Providers</h2>
                        <p class="text-gray-500 text-sm">Airlines, hotels &amp; cab companies manage services.</p>
                        <div class="mt-4 text-[#0EA5E9] text-xs font-bold uppercase tracking-widest">ENTER &nearr;</div>
                    </a>
                </div>
            </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($tab === 'counties'): ?>
            <div class="bg-white border border-gray-200 rounded-2xl p-6">
                <h3 class="font-bold text-gray-900 mb-5">47 County Exhibitors — independent websites</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="text-left text-[10px] uppercase tracking-widest text-gray-400 border-b border-gray-100">
                            <th class="pb-3 pr-4">County</th><th class="pb-3 pr-4">Products</th><th class="pb-3 pr-4">Trade Volume</th><th class="pb-3">Website</th>
                        </tr></thead>
                        <tbody>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $counties; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <tr class="border-b border-gray-50 last:border-0">
                            <td class="py-2.5 pr-4 font-semibold text-gray-900"><?php echo e($c->name); ?></td>
                            <td class="py-2.5 pr-4 text-gray-500"><?php echo e($c->product_count); ?></td>
                            <td class="py-2.5 pr-4 font-bold text-gray-900">KES <?php echo e(number_format($c->trade_volume)); ?></td>
                            <td class="py-2.5"><a href="<?php echo e(route('counties.show', $c->slug)); ?>" class="text-[10px] font-bold px-2.5 py-1 rounded-full border border-gray-200 text-gray-500 hover:text-gray-900">OPEN &nearr;</a></td>
                        </tr>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($tab === 'exhibitors'): ?>
            <div class="bg-white border border-gray-200 rounded-2xl p-6">
                <h3 class="font-bold text-gray-900 mb-5">Private Exhibitors (<?php echo e($exhibitors->count()); ?>)</h3>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $exhibitors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <div class="flex items-center gap-4 py-3 border-b border-gray-100 last:border-0">
                    <div class="w-10 h-10 rounded-full bg-[#2D6A4F] flex items-center justify-center text-gray-900 font-black text-xs shrink-0"><?php echo e(strtoupper(substr($e->name, 0, 2))); ?></div>
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold text-gray-900 text-sm"><?php echo e($e->name); ?></div>
                        <div class="text-xs text-gray-400"><?php echo e($e->email); ?> &middot; <?php echo e($e->county?->name); ?> County &middot; <?php echo e($e->product_count); ?> products</div>
                    </div>
                    <a href="<?php echo e(route('exhibitor.site', \Illuminate\Support\Str::slug($e->name))); ?>" class="text-[10px] font-bold px-2.5 py-1 rounded-full border border-gray-200 text-gray-500 hover:text-gray-900">STOREFRONT &nearr;</a>
                </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($tab === 'national'): ?>
            <div class="grid md:grid-cols-2 gap-4">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $ministries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <div class="bg-white border border-gray-200 rounded-2xl p-6">
                    <div class="flex items-start justify-between mb-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-gray-900 font-black text-xs" style="background: <?php echo e($m->color ?: '#0EA5E9'); ?>"><?php echo e($m->code); ?></div>
                        <a href="<?php echo e(route('national.site', $m->slug)); ?>" class="text-[10px] font-bold px-2.5 py-1 rounded-full border border-gray-200 text-gray-500 hover:text-gray-900">WEBSITE &nearr;</a>
                    </div>
                    <div class="font-bold text-gray-900 mb-1"><?php echo e($m->name); ?></div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400"><?php echo e($m->agencies->count()); ?> agencies</div>
                </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($tab === 'providers'): ?>
            <div class="grid lg:grid-cols-2 gap-6">
                <div class="bg-white border border-gray-200 rounded-2xl p-6">
                    <h3 class="font-bold text-gray-900 mb-5">Certified Providers (<?php echo e($providers->count()); ?>)</h3>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $providers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <div class="flex items-center gap-4 py-3 border-b border-gray-100 last:border-0">
                        <div class="w-10 h-10 rounded-full bg-[#0EA5E9] flex items-center justify-center text-gray-900 font-black text-xs shrink-0"><?php echo e(strtoupper(substr($p->name, 0, 2))); ?></div>
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-gray-900 text-sm"><?php echo e($p->name); ?></div>
                            <div class="text-xs text-gray-400"><?php echo e($p->email); ?> · <?php echo e(($p->metadata['provider_type'] ?? 'provider')); ?></div>
                        </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($p->metadata['approved'] ?? false): ?>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-600">CERTIFIED</span>
                        <?php else: ?>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-50 text-amber-600">PENDING</span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </div>
                <div class="bg-white border border-gray-200 rounded-2xl p-6">
                    <h3 class="font-bold text-gray-900 mb-5">Certification Queue (<?php echo e($pendingServices->count()); ?>)</h3>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $pendingServices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0">
                        <div>
                            <div class="font-semibold text-gray-900 text-sm"><?php echo e($s['label']); ?></div>
                            <div class="text-xs text-gray-400">KES <?php echo e(number_format($s['price'])); ?> · <?php echo e($s['table']); ?></div>
                        </div>
                        <form method="POST" action="<?php echo e(route('kicc.admin.approve', [$s['table'], $s['id']])); ?>"><?php echo csrf_field(); ?>
                            <button class="text-[10px] font-bold px-3 py-1.5 rounded-lg text-gray-900 bg-emerald-600 hover:bg-emerald-700">CERTIFY ✓</button>
                        </form>
                    </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    <p class="text-gray-400 text-sm py-6 text-center">Queue clear — nothing awaiting certification.</p>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($tab === 'orders'): ?>
            <div class="bg-white border border-gray-200 rounded-2xl p-6">
                <h3 class="font-bold text-gray-900 mb-5">All Orders</h3>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $o): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <div class="py-4 border-b border-gray-100 last:border-0">
                    <div class="flex items-center justify-between mb-2">
                        <div class="font-semibold text-gray-900 text-sm"><?php echo e($o->order_number); ?></div>
                        <div class="text-xs text-gray-400"><?php echo e($o->created_at?->format('d M Y H:i')); ?></div>
                    </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $o->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <div class="flex justify-between text-xs text-gray-500 py-1"><span><?php echo e($item->product_name); ?> × <?php echo e($item->quantity); ?></span><span class="font-bold text-gray-900">KES <?php echo e(number_format($item->total)); ?></span></div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    <div class="mt-2 flex gap-2">
                        <span class="text-[10px] font-bold px-2 py-1 rounded-full bg-gray-100 text-gray-600"><?php echo e(strtoupper($o->payment_status ?? 'pending')); ?></span>
                        <span class="text-[10px] font-bold px-2 py-1 rounded-full bg-gray-100 text-gray-600"><?php echo e(strtoupper($o->fulfillment_status ?? 'unfulfilled')); ?></span>
                    </div>
                </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                <p class="text-gray-400 text-sm py-6 text-center">No orders yet.</p>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($tab === 'escrow'): ?>
            <div class="bg-white border border-gray-200 rounded-2xl p-6">
                <h3 class="font-bold text-gray-900 mb-5">All Escrow Transactions</h3>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $escrows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0">
                    <div>
                        <div class="font-semibold text-gray-900 text-sm"><?php echo e($e->escrow_id); ?></div>
                        <div class="text-xs text-gray-400"><?php echo e($e->buyer?->name ?? 'Guest'); ?> &rarr; <?php echo e($e->seller?->name); ?> &middot; <?php echo e($e->created_at?->format('d M Y')); ?></div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="text-right">
                            <div class="font-black text-gray-900">KES <?php echo e(number_format($e->amount)); ?></div>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full <?php echo e($e->status === 'released' ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600'); ?>"><?php echo e(strtoupper($e->status)); ?></span>
                        </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($e->status === 'held'): ?>
                        <form method="POST" action="<?php echo e(route('kicc.admin.escrow.release', $e->id)); ?>" onsubmit="return confirm('Release KES <?php echo e(number_format($e->amount)); ?> to <?php echo e($e->seller?->name); ?>?')"><?php echo csrf_field(); ?><button class="text-[10px] font-bold px-2.5 py-1.5 rounded-lg text-gray-900" style="background: <?php echo e($accent); ?>">RELEASE</button></form>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                <p class="text-gray-400 text-sm py-6 text-center">No escrow transactions yet.</p>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($tab === 'users'): ?>
            <div class="bg-white border border-gray-200 rounded-2xl p-6">
                <h3 class="font-bold text-gray-900 mb-5">Platform Users</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="text-left text-[10px] uppercase tracking-widest text-gray-400 border-b border-gray-100">
                            <th class="pb-3 pr-4">Name</th><th class="pb-3 pr-4">Email</th><th class="pb-3 pr-4">Type</th><th class="pb-3">Roles</th>
                        </tr></thead>
                        <tbody>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <tr class="border-b border-gray-50 last:border-0">
                            <td class="py-2.5 pr-4 font-semibold text-gray-900"><?php echo e($u->name); ?></td>
                            <td class="py-2.5 pr-4 text-gray-500 text-xs"><?php echo e($u->email); ?></td>
                            <td class="py-2.5 pr-4 text-gray-500 text-xs"><?php echo e($u->account_type); ?></td>
                            <td class="py-2.5 text-gray-500 text-xs"><?php echo e($u->roles->pluck('name')->implode(', ') ?: '—'); ?></td>
                        </tr>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.blank', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/kicc/Desktop/kicc/kicc-admin/resources/views/dashboards/kicc.blade.php ENDPATH**/ ?>