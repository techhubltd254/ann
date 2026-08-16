<?php $__env->startSection('title', 'Sign In — KICC Admin'); ?>

<?php $__env->startSection('content'); ?>
<div class="min-h-screen bg-[#F9FAFB] flex items-center justify-center p-5">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 50 51' fill='%23901C1E'%3E%3Cpath d='M49.6 11.6v11l-9.2 5.3v10.5l-19.2 11-.1.1-.2.1h-.4l-.1-.1-19.2-11V7.6l9.6-5.5h.8l9.6 5.5v20.6l8-4.6V13.5l9.6-5.5h.8l9.6 5.5z'/%3E%3C/svg%3E" alt="KICC" class="h-16 w-auto mx-auto mb-4">
            <h1 class="text-2xl font-black text-gray-900">KICC Admin</h1>
            <p class="text-gray-500 text-sm mt-2">Sign in to manage the platform</p>
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl p-7">
            <form method="POST" action="<?php echo e(route('login')); ?>">
                <?php echo csrf_field(); ?>
                <div class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Email</label>
                        <input type="email" name="login" value="admin@kicc.go.ke" required
                               class="w-full bg-gray-50 border border-gray-200 focus:border-blue-500/50 rounded-xl px-4 py-2.5 text-sm text-gray-900 outline-none transition-colors">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['login'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Password</label>
                        <input type="password" name="password" required
                               class="w-full bg-gray-50 border border-gray-200 focus:border-blue-500/50 rounded-xl px-4 py-2.5 text-sm text-gray-900 outline-none transition-colors">
                    </div>
                </div>
                <button type="submit"
                        class="w-full inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 mt-6 px-8 text-base h-14 rounded-xl bg-blue-600 text-white hover:bg-blue-700">
                    Sign In
                </button>
            </form>
        </div>

        <div class="mt-6 text-center text-xs text-gray-400">
            &copy; <?php echo e(date('Y')); ?> KICC — Platform Admin Panel
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.blank', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/kicc/Desktop/kicc/kicc-admin/resources/views/auth/login.blade.php ENDPATH**/ ?>