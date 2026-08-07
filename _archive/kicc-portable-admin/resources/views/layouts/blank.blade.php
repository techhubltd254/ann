<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'KICC Admin')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { kicc: { bg: '#07090F', card: '#0D1220', surface: '#141B2E', red: '#901C1E', gold: '#FFCD05', navy: '#0B1E57', emerald: '#2D6A4F' } } } } }
    </script>
    <style>* { font-family: 'Inter', system-ui, sans-serif; } body { background-color: #07090F; color: #fff; }</style>
</head>
<body class="antialiased">
    @yield('content')
</body>
</html>