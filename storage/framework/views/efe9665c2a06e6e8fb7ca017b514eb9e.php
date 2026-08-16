<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $__env->yieldContent('title'); ?> - KICC Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?php echo e(asset('js/theme.js')); ?>"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', system-ui, sans-serif; }
        body { background-color: #07090F; color: #ffffff; }
        .scrollbar-hide { scrollbar-width: none; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
    </style>
</head>
<body class="antialiased text-gray-900">
    <?php echo $__env->yieldContent('content'); ?>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html><?php /**PATH /home/kicc/Desktop/kicc/kicc-admin/resources/views/layouts/blank.blade.php ENDPATH**/ ?>