<?php $__env->startSection('title', $sectorInfo['title'] . ' — ' . $county->name . ' County'); ?>
<?php $__env->startSection('description', 'Explore ' . $sectorInfo['title'] . ' in ' . $county->name . ' County'); ?>

<?php $__env->startSection('content'); ?>
<div class="pt-20">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($fourDVideo): ?>
    <div class="relative h-[40vh] md:h-[50vh] overflow-hidden bg-black">
        <video autoplay muted loop playsinline class="w-full h-full object-cover">
            <source src="<?php echo e($fourDVideo); ?>" type="video/mp4">
        </video>
        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
        <div class="absolute bottom-0 left-0 right-0 max-w-7xl mx-auto px-5 pb-8">
            <a href="<?php echo e(route('counties.show', $county->slug)); ?>" class="inline-flex items-center gap-1.5 text-white/60 hover:text-white text-sm mb-3 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                <?php echo e($county->name); ?> County
            </a>
            <h1 class="text-3xl md:text-5xl font-black text-white" data-split><?php echo e($sectorInfo['title']); ?></h1>
            <p class="text-white/70 text-sm mt-2"><?php echo e($sectorInfo['desc']); ?></p>
        </div>
    </div>
    <?php else: ?>
    <div class="bg-white border-b border-gray-200 py-12">
        <div class="max-w-7xl mx-auto px-5">
            <a href="<?php echo e(route('counties.show', $county->slug)); ?>" class="inline-flex items-center gap-1.5 text-[#5A6480] hover:text-gray-900 text-sm mb-4 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                <?php echo e($county->name); ?> County
            </a>
            <div class="flex items-center gap-5">
                <div class="w-16 h-16 bg-[#901C1E]/20 border border-[#901C1E]/30 rounded-2xl flex items-center justify-center shrink-0">
                    <svg class="w-7 h-7 text-[#901C1E]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
                <div>
                    <h1 class="text-3xl font-black text-gray-900" data-split><?php echo e($sectorInfo['title']); ?></h1>
                    <p class="text-[#5A6480] mt-1 text-sm"><?php echo e($items->count()); ?> registered entities · <?php echo e($county->name); ?> County</p>
                </div>
            </div>
            <p class="text-[#5A6480] text-sm mt-4 max-w-xl"><?php echo e($sectorInfo['desc']); ?></p>
        </div>
    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <div class="max-w-7xl mx-auto px-5 py-10">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($items->count() > 0): ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
            <div class="group bg-white rounded-2xl overflow-hidden border border-gray-200 hover:border-[#FFCD05]/40 hover:shadow-md transition-all">
                <div class="h-40 overflow-hidden bg-gradient-to-br from-[#F9FAFB] to-gray-100 relative">
                    
                    <div class="absolute inset-0 flex items-center justify-center bg-gradient-to-br from-[#0A1024] to-[#901C1E]/50">
                        <svg class="w-10 h-10 text-white/20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <?php $holo = '/media/derivatives/holo/' . $county->slug . '-' . $sector . '-' . $e->id . '/wiggle.mp4'; ?>
                    <video autoplay muted loop playsinline preload="none"
                           poster="<?php echo e(media('counties/' . $county->slug . '/' . $sector . '/' . $e->id . '.jpeg')); ?>"
                           class="relative w-full h-full object-cover bg-[#F9FAFB] group-hover:scale-105 transition-transform duration-500"
                           onerror="this.remove()">
                        <source src="<?php echo e($holo); ?>" type="video/mp4">
                    </video>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($e->category): ?>
                    <span class="absolute top-2.5 left-3 text-[10px] font-bold px-2 py-0.5 rounded-full bg-black/40 text-white/90 backdrop-blur-sm capitalize"><?php echo e($e->category); ?></span>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
                <div class="p-4">
                    <div class="font-bold text-gray-900 text-sm leading-snug"><?php echo e($e->name); ?></div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($e->description): ?>
                    <p class="text-gray-500 text-xs leading-relaxed mt-1.5 line-clamp-2"><?php echo e($e->description); ?></p>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <div class="flex flex-wrap gap-x-3 gap-y-1 mt-2.5 text-[11px] text-gray-400">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($e->location): ?><span>📍 <?php echo e($e->location); ?></span><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($e->entry_fee)): ?><span class="font-bold text-kicc-gold">KES <?php echo e(number_format($e->entry_fee)); ?></span><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($e->contact)): ?><span>☎ <?php echo e($e->contact); ?></span><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>
            </div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
        </div>
        <div class="mt-8">
            <?php echo e($items->links()); ?>

        </div>
        <?php else: ?>
<div class="text-center py-16 text-[#5A6480]">
        <span class="text-4xl block mb-3">📂</span>
        <p class="text-sm">No entities registered in this sector yet.</p>
    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($services->count() > 0): ?>
    <div class="mt-16 pt-10 border-t border-gray-200">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h2 class="text-2xl font-black text-gray-900">Bookable Services</h2>
                <p class="text-[#5A6480] text-sm mt-1">Add services to your cart and checkout securely.</p>
            </div>
            <a href="<?php echo e(route('cart.index')); ?>" class="text-sm font-bold text-kicc-gold hover:text-[#FFCD05] transition-colors">
                View Cart →
            </a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $services; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
            <div class="bg-white rounded-2xl overflow-hidden border border-gray-200 hover:border-[#FFCD05]/40 hover:shadow-md transition-all">
                <div class="h-36 bg-gradient-to-br from-[#F9FAFB] to-gray-100 flex items-center justify-center">
                    <img src="https://placehold.co/400x300/1a1a2e/FFCD05?text=Service" alt="" class="w-full h-full object-cover" loading="lazy">
                </div>
                <div class="p-4">
                    <h3 class="font-bold text-gray-900 text-sm leading-snug"><?php echo e($product->name); ?></h3>
                    <p class="text-gray-500 text-xs leading-relaxed mt-1.5 line-clamp-2"><?php echo e($product->short_description ?? $product->description); ?></p>
                    <div class="mt-3 space-y-1.5">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $product->variants->where('is_active', true); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $variant): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <form method="POST" action="<?php echo e(route('cart.add')); ?>" class="flex items-center justify-between gap-2 p-2 rounded-lg hover:bg-gray-50 transition-colors">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="variant_id" value="<?php echo e($variant->id); ?>">
                            <div class="flex-1 min-w-0">
                                <div class="text-xs text-gray-700 truncate"><?php echo e($variant->name); ?></div>
                                <div class="text-xs font-bold text-kicc-gold">KES <?php echo e(number_format($variant->price)); ?></div>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <select name="quantity" class="text-xs border border-gray-200 rounded-lg px-1.5 py-1 w-14 bg-white">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php for($q = 1; $q <= min(10, $variant->stock ?? 10); $q++): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                    <option value="<?php echo e($q); ?>"><?php echo e($q); ?></option>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endfor; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                </select>
                                <button type="submit" class="text-[10px] font-bold px-2.5 py-1.5 rounded-lg bg-[#901C1E] text-white hover:bg-[#7b1618] transition-colors whitespace-nowrap">
                                    Add to Cart
                                </button>
                            </div>
                        </form>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </div>
                </div>
            </div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
        </div>
    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/kicc/Desktop/kicc/kicc-platform/resources/views/counties/sector.blade.php ENDPATH**/ ?>